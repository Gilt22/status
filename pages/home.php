<?php
$settings = getSiteSettings();

// Hole alle Vorfälle für den konfigurierten Zeitraum
$incidents = getIncidents(null, $settings['incident_days']);
$hostGroups = getAllHostGroups();
$hosts = getHosts();

// Status-Logik mit gleicher Behandlung von resolved und completed
$groupStatus = [];
$hostStatus = [];
$statusPriority = [
    'investigating' => 1,
    'identified' => 2,
    'progress' => 2,
    'monitoring' => 3,
    'planned' => 4,
    'resolved' => 5,
    'completed' => 5,
];

// Berechne Status für Gruppen und Hosts...
foreach ($hostGroups as $group) {
    $groupStatus[$group['id']] = 'operational';
    foreach ($incidents as $incident) {
        $incidentDetails = getIncident($incident['id']);
        // Behandle resolved und completed gleich
        if ($incidentDetails && $incident['status'] != 'resolved' && $incident['status'] != 'completed') {
            // Für geplante Wartungen prüfen, ob der aktuelle Zeitpunkt im Wartungszeitraum liegt
            if ($incident['status'] == 'planned') {
                $scheduledStart = isset($incident['scheduled_start']) ? strtotime($incident['scheduled_start']) : null;
                $scheduledEnd = isset($incident['scheduled_end']) ? strtotime($incident['scheduled_end']) : null;
                $currentTime = time();
                
                // Nur berücksichtigen, wenn die aktuelle Zeit im Wartungszeitraum liegt
                if (!$scheduledStart || !$scheduledEnd || $currentTime < $scheduledStart || $currentTime > $scheduledEnd) {
                    continue; // Wartung liegt nicht im aktuellen Zeitraum, überspringen
                }
            }
            
            foreach ($incidentDetails['affected_groups'] as $affectedGroup) {
                if ($affectedGroup['id'] == $group['id']) {
                    $currentStatus = $groupStatus[$group['id']];
                    $newStatus = $incident['status'];
                    if ($currentStatus == 'operational' || 
                        (isset($statusPriority[$newStatus]) && isset($statusPriority[$currentStatus]) && 
                         $statusPriority[$newStatus] < $statusPriority[$currentStatus])) {
                        $groupStatus[$group['id']] = $newStatus;
                    }
                }
            }
        }
    }
}

foreach ($hosts as $host) {
    $hostStatus[$host['id']] = 'operational';
    if (isset($host['group_id']) && isset($groupStatus[$host['group_id']]) && $groupStatus[$host['group_id']] != 'operational') {
        $hostStatus[$host['id']] = $groupStatus[$host['group_id']];
    }
    foreach ($incidents as $incident) {
        $incidentDetails = getIncident($incident['id']);
        // Behandle resolved und completed gleich
        if ($incidentDetails && $incident['status'] != 'resolved' && $incident['status'] != 'completed') {
            // Für geplante Wartungen prüfen, ob der aktuelle Zeitpunkt im Wartungszeitraum liegt
            if ($incident['status'] == 'planned') {
                $scheduledStart = isset($incident['scheduled_start']) ? strtotime($incident['scheduled_start']) : null;
                $scheduledEnd = isset($incident['scheduled_end']) ? strtotime($incident['scheduled_end']) : null;
                $currentTime = time();
                
                // Nur berücksichtigen, wenn die aktuelle Zeit im Wartungszeitraum liegt
                if (!$scheduledStart || !$scheduledEnd || $currentTime < $scheduledStart || $currentTime > $scheduledEnd) {
                    continue; // Wartung liegt nicht im aktuellen Zeitraum, überspringen
                }
            }
            
            foreach ($incidentDetails['affected_hosts'] as $affectedHost) {
                if ($affectedHost['id'] == $host['id']) {
                    $currentStatus = $hostStatus[$host['id']];
                    $newStatus = $incident['status'];
                    if ($currentStatus == 'operational' || 
                        (isset($statusPriority[$newStatus]) && isset($statusPriority[$currentStatus]) && 
                         $statusPriority[$newStatus] < $statusPriority[$currentStatus])) {
                        $hostStatus[$host['id']] = $newStatus;
                    }
                }
            }
        }
    }
}

// E-Mail-Anmeldung verarbeiten
$subscribeMessage = '';
$subscribeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if (empty($email)) {
        $subscribeError = 'Bitte geben Sie eine E-Mail-Adresse ein.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subscribeError = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } else {
        $token = addSubscriber($email);
        if ($token) {
            $subscribeMessage = 'Vielen Dank! Bitte bestätigen Sie Ihre E-Mail-Adresse.';
        } else {
            $subscribeError = 'Diese E-Mail-Adresse ist bereits registriert oder es ist ein Fehler aufgetreten.';
        }
    }
}

// Generiere 24h Timeline-Daten für jede Gruppe
$timelineData = [];
foreach ($hostGroups as $group) {
    $timelineData[$group['id']] = [];
    for ($i = 0; $i < 24; $i++) {
        $timelineData[$group['id']][$i] = [
            'status' => 'operational',
            'incident' => null
        ];
    }
}

// Fülle Timeline mit echten Incident-Daten
foreach ($incidents as $incident) {
    $incidentDetails = getIncident($incident['id']);
    if ($incidentDetails) {
        $startHour = floor((time() - strtotime($incident['created_at'])) / 3600);
        // Behandle resolved und completed gleich
        $endHour = ($incident['resolved_at'] || $incident['status'] == 'completed') ? 
            floor((time() - strtotime($incident['resolved_at'])) / 3600) : 0;
        
        foreach ($incidentDetails['affected_groups'] as $affectedGroup) {
            for ($i = min(23, $startHour); $i >= max(0, $endHour); $i--) {
                if (isset($timelineData[$affectedGroup['id']][$i])) {
                    $timelineData[$affectedGroup['id']][$i] = [
                        'status' => $incident['status'],
                        'incident' => $incident
                    ];
                }
            }
        }
    }
}

// Status-Text-Mapping
$statusTexts = [
    'operational' => 'Betriebsbereit',
    'planned' => 'Geplante Wartung',
    'investigating' => 'Wird untersucht',
    'progress' => 'In Bearbeitung',
    'identified' => 'Problem erkannt',
    'monitoring' => 'Wird überwacht',
    'resolved' => 'Behoben',
    'completed' => 'Abgeschlossen'
];

// Berechne Gesamtstatus
$allOperational = true;
foreach ($hostGroups as $group) {
    if ($groupStatus[$group['id']] != 'operational') {
        $allOperational = false;
        break;
    }
}

// Funktion zum Anzeigen eines Vorfalls
function renderIncident($incident) {
    global $statusTexts;
    ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h4 class="h6 mb-0">
                    <a href="index.php?page=incident&id=<?php echo $incident['id']; ?>" 
                       class="text-decoration-none text-dark">
                        <?php echo h($incident['title']); ?>
                    </a>
                </h4>
                <span class="badge rounded-pill ms-2
                    <?php
                    switch($incident['status']) {
                        case 'operational':
                            echo 'badge-operational';
                            break;
                        case 'investigating':
                            echo 'badge-investigating';
                            break;
                        case 'progress':
                            echo 'badge-progress';
                            break;
                        case 'identified':
                            echo 'badge-identified';
                            break;
                        case 'monitoring':
                            echo 'badge-monitoring';
                            break;
                        case 'planned':
                            echo 'badge-planned';
                            break;
                        case 'completed':
                            echo 'badge-resolved'; // Gleiche Klasse wie resolved
                            break;
                        default:
                            echo 'badge-resolved';
                        }
                    ?>">
                    <?php echo $statusTexts[$incident['status']] ?? $incident['status']; ?>
                </span>
            </div>
            
            <div class="text-muted small mb-3">
                <?php if ($incident['status'] === 'planned'): ?>
                    <?php
                    $scheduledStart = isset($incident['scheduled_start']) ? strtotime($incident['scheduled_start']) : null;
                    $scheduledEnd = isset($incident['scheduled_end']) ? strtotime($incident['scheduled_end']) : null;
                    ?>
                    <?php if ($scheduledStart): ?>
                        <p class="mb-1">
                            <i class="bi bi-calendar me-1"></i>
                            Geplant für: <?php echo date('d.m.Y H:i', $scheduledStart); ?>
                            <?php if ($scheduledEnd): ?>
                                - <?php echo date('d.m.Y H:i', $scheduledEnd); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mb-1">
                        <i class="bi bi-clock me-1"></i>
                        Gemeldet: <?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?>
                    </p>
                    <?php if (($incident['status'] === 'resolved' || $incident['status'] === 'completed') && $incident['resolved_at']): ?>
                        <p class="mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            Behoben: <?php echo date('d.m.Y H:i', strtotime($incident['resolved_at'])); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <?php echo nl2br(h($incident['description'])); ?>
            </div>
            
            <a href="index.php?page=incident&id=<?php echo $incident['id']; ?>" 
               class="btn btn-sm btn-outline-primary">
                Details anzeigen
            </a>
        </div>
    </div>
    <?php
}

// Vorfälle nach scheduled_start sortieren
usort($incidents, function($a, $b) {
    // Verwende scheduled_start für die Sortierung
    $dateA = isset($a['scheduled_start']) ? strtotime($a['scheduled_start']) : strtotime($a['created_at']);
    $dateB = isset($b['scheduled_start']) ? strtotime($b['scheduled_start']) : strtotime($b['created_at']);
    
    return $dateB - $dateA; // Absteigend sortieren (neueste zuerst)
});

$incidentDays = $settings['incident_days'];

// Vorfälle nach Tagen gruppieren
$incidentsByDay = [];
$today = strtotime('today');

// Zeitraum festlegen: Vergangene Tage + Heute + Zukünftige Tage mit geplanten Vorfällen
$startDate = date('Y-m-d', strtotime('-' . $incidentDays . ' days'));
$endDate = date('Y-m-d', strtotime('+30 days')); // Für zukünftige geplante Vorfälle

// Alle Vorfälle nach Tagen gruppieren
foreach ($incidents as $incident) {
    // Datum bestimmen (scheduled_start für alle Vorfälle)
    $incidentDate = isset($incident['scheduled_start']) ? 
                    date('Y-m-d', strtotime($incident['scheduled_start'])) : 
                    date('Y-m-d', strtotime($incident['created_at']));
    
    // Nur Vorfälle im definierten Zeitraum berücksichtigen
    if ($incidentDate >= $startDate && $incidentDate <= $endDate) {
        if (!isset($incidentsByDay[$incidentDate])) {
            $incidentsByDay[$incidentDate] = [];
        }
        $incidentsByDay[$incidentDate][] = $incident;
    }
}

// Alle Tage im Zeitraum erstellen (auch ohne Vorfälle)
$allDays = [];
$currentDate = $startDate;
while ($currentDate <= $endDate) {
    // Nur Tage mit Vorfällen oder im Bereich der letzten X Tage hinzufügen
    if (isset($incidentsByDay[$currentDate]) || $currentDate <= date('Y-m-d')) {
        $allDays[$currentDate] = isset($incidentsByDay[$currentDate]) ? $incidentsByDay[$currentDate] : [];
    }
    $currentDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
}

// Teile die Tage in zukünftige und vergangene auf
$futureDays = [];
$pastDays = [];
$todayStr = date('Y-m-d');

foreach ($allDays as $date => $dayIncidents) {
    if (strtotime($date) > $today) {
        // Nur zukünftige Tage mit Vorfällen hinzufügen
        if (!empty($dayIncidents)) {
            $futureDays[$date] = $dayIncidents;
        }
    } else {
        $pastDays[$date] = $dayIncidents;
    }
}

// Sortiere die Tage (chronologisch für zukünftige, umgekehrt chronologisch für vergangene)
ksort($futureDays);
krsort($pastDays);

// Funktion zum Rendern eines Tages mit seinen Vorfällen
function renderDay($date, $incidents) {
    $dateTime = strtotime($date);
    $isToday = date('Y-m-d', $dateTime) === date('Y-m-d');
    $isTomorrow = date('Y-m-d', $dateTime) === date('Y-m-d', strtotime('+1 day'));
    $isYesterday = date('Y-m-d', $dateTime) === date('Y-m-d', strtotime('-1 day'));
    
    // Bestimme das Datum-Label
    if ($isToday) {
        $dateLabel = 'Heute';
    } elseif ($isTomorrow) {
        $dateLabel = 'Morgen';
    } elseif ($isYesterday) {
        $dateLabel = 'Gestern';
    } else {
        $dateLabel = date('d.m.Y', $dateTime);
    }
    ?>
    <div class="mb-4">
        <h3 class="h5 mb-3"><?php echo $dateLabel; ?></h3>
        
        <?php if (empty($incidents)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="text-muted mb-0">
                        Keine Vorfälle an diesem Tag.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($incidents as $incident): ?>
                <?php renderIncident($incident); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

if($settings['layout'] == 1){
?>
<div class="mb-5">
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3
                 <?php echo $allOperational ? 'bg-success' : 'bg-warning'; ?> bg-opacity-10"
                 style="width: 80px; height: 80px;">
                <span class="display-6">
                    <?php echo $allOperational ? '✓' : '!'; ?>
                </span>
            </div>
            <h2 class="h3 fw-bold">
                <?php echo $allOperational ? 'Alle Systeme funktionieren' : 'Es gibt aktuelle Meldungen'; ?>
            </h2>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($hostGroups as $group): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0"><?php echo h($group['name']); ?></h3>
                        <span class="badge rounded-pill
                            <?php
                            switch($groupStatus[$group['id']]) {
                                case 'operational':
                                    echo 'badge-operational';
                                    break;
                                case 'investigating':
                                    echo 'badge-investigating';
                                    break;
                                case 'progress':
                                    echo 'badge-progress';
                                    break;
                                case 'identified':
                                    echo 'badge-identified';
                                    break;
                                case 'monitoring':
                                    echo 'badge-monitoring';
                                    break;
                                case 'planned':
                                    echo 'badge-planned';
                                    break;
                                case 'completed':
                                    echo 'badge-resolved'; // Gleiche Klasse wie resolved
                                    break;
                                default:
                                    echo 'badge-resolved';
                            }
                            ?>">
                            <?php echo $statusTexts[$groupStatus[$group['id']]] ?? $groupStatus[$group['id']]; ?>
                        </span>
                    </div>

                    <!-- 24h Timeline -->
                    <div class="d-flex gap-1 mb-4 rounded overflow-hidden">
                        <?php for ($i = 23; $i >= 0; $i--): ?>
                            <?php
                            $timelineSlot = $timelineData[$group['id']][$i];
                            $statusClass = match($timelineSlot['status']) {
                                'operational' => 'status-operational',
                                'investigating' => 'status-investigating',
                                'progress' => 'status-progress',
                                'identified' => 'status-identified',
                                'monitoring' => 'status-monitoring',
                                'completed' => 'status-resolved', // Gleiche Klasse wie resolved
                                default => 'status-resolved'
                            };
                            ?>
                            <div class="flex-grow-1 timeline-bar <?php echo $statusClass; ?>"
                                 <?php if ($timelineSlot['incident']): ?>
                                 title="<?php echo h($timelineSlot['incident']['title']); ?>"
                                 <?php endif; ?>></div>
                        <?php endfor; ?>
                    </div>

                    <?php
                    // Hosts für diese Gruppe
                    $groupHosts = array_filter($hosts, function($host) use ($group) {
                        return $host['group_id'] == $group['id'];
                    });
                    
                    if (!empty($groupHosts)):
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($groupHosts as $host): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="fw-medium"><?php echo h($host['name']); ?></span>
                                    <span class="badge rounded-pill
                                        <?php
                                        switch($hostStatus[$host['id']]) {
                                            case 'operational':
                                                echo 'badge-operational';
                                                break;
                                            case 'investigating':
                                                echo 'badge-investigating';
                                                break;
                                            case 'progress':
                                                echo 'badge-progress';
                                                break;
                                            case 'identified':
                                                echo 'badge-identified';
                                                break;
                                            case 'monitoring':
                                                echo 'badge-monitoring';
                                                break;
                                            case 'planned':
                                                echo 'badge-planned';
                                                break;
                                            case 'completed':
                                                echo 'badge-resolved'; // Gleiche Klasse wie resolved
                                                break;
                                            default:
                                                echo 'badge-resolved';
                                            }
                                        ?>">
                                        <?php echo $statusTexts[$hostStatus[$host['id']]] ?? $hostStatus[$host['id']]; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($futureDays)): ?>
<div class="mt-5">
    <h2 class="h4 mb-4">Geplante Vorfälle</h2>
    
    <?php foreach ($futureDays as $date => $incidents): ?>
        <?php renderDay($date, $incidents); ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="mt-5">
    <h2 class="h4 mb-4">Vorfälle der letzten <?php echo $incidentDays; ?> Tage</h2>
    
    <?php if (empty($pastDays)): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <p class="text-muted mb-0">
                    Keine Vorfälle in den letzten <?php echo $incidentDays; ?> Tagen.
                </p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($pastDays as $date => $incidents): ?>
            <?php renderDay($date, $incidents); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="mt-5">
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <h2 class="h3 mb-2">Benachrichtigungen erhalten</h2>
            <p class="text-muted mb-4">Erhalten Sie Benachrichtigungen über neue Störungen und Updates per E-Mail.</p>
            
            <?php if (!empty($subscribeMessage)): ?>
                <div class="alert alert-success mb-4">
                    <?php echo h($subscribeMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($subscribeError)): ?>
                <div class="alert alert-danger mb-4">
                    <?php echo h($subscribeError); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="mx-auto" style="max-width: 400px;">
                <div class="input-group">
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Ihre E-Mail-Adresse" 
                           required>
                    <button type="submit" 
                            name="subscribe" 
                            class="btn btn-primary">
                        Anmelden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
} elseif($settings['layout'] == 2){
?>
<div class="mb-5">
    <div class="card bg-gradient-primary text-white shadow-lg">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle 
                         <?php echo $allOperational ? 'bg-success' : 'bg-warning'; ?> bg-opacity-25"
                         style="width: 60px; height: 60px;">
                        <span class="display-6 text-white">
                            <?php echo $allOperational ? '✓' : '!'; ?>
                        </span>
                    </div>
                </div>
                <div class="col">
                    <h2 class="h3 fw-bold mb-0">
                        <?php echo $allOperational ? 'Alle Systeme funktionieren' : 'Es gibt aktuelle Störungen'; ?>
                    </h2>
                    <p class="mb-0 opacity-75">
                        Status-Dashboard | <?php echo date('d.m.Y H:i'); ?>
                    </p>
                </div>
                <div class="col-auto">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-arrow-clockwise me-2"></i>Aktualisieren</a></li>
                            <li><a class="dropdown-item" href="#subscribe-section"><i class="bi bi-bell me-2"></i>Benachrichtigungen</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status-Übersicht als Kacheln -->
<div class="row g-4 mb-5">
    <?php 
    // Zähle die Anzahl der Systeme in jedem Status
    $statusCount = [
        'operational' => 0,
        'investigating' => 0,
        'identified' => 0,
        'progress' => 0,
        'monitoring' => 0,
        'planned' => 0,
        'resolved' => 0,
        'completed' => 0  // Hinzugefügt für completed
    ];
    
    foreach ($hostGroups as $group) {
        $status = $groupStatus[$group['id']];
        if (isset($statusCount[$status])) {
            $statusCount[$status]++;
        }
    }
    
    // Kombiniere resolved und completed für die Anzeige
    $statusCount['resolved'] += $statusCount['completed'];
    
    // Status-Karten mit Icons
    $statusCards = [
        [
            'status' => 'operational',
            'icon' => 'check-circle-fill',
            'title' => 'Betriebsbereit',
            'color' => 'success'
        ],
        [
            'status' => 'investigating',
            'icon' => 'search',
            'title' => 'Wird untersucht',
            'color' => 'warning'
        ],
        [
            'status' => 'progress',
            'icon' => 'tools',
            'title' => 'In Bearbeitung',
            'color' => 'info'
        ],
        [
            'status' => 'planned',
            'icon' => 'calendar-event',
            'title' => 'Geplant',
            'color' => 'primary'
        ]
    ];
    
    foreach ($statusCards as $card):
    ?>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle 
                                 bg-<?php echo $card['color']; ?> bg-opacity-10"
                                 style="width: 48px; height: 48px;">
                                <i class="bi bi-<?php echo $card['icon']; ?> text-<?php echo $card['color']; ?> fs-4"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="h6 mb-0"><?php echo $card['title']; ?></h3>
                            <p class="fs-4 fw-bold mb-0"><?php echo $statusCount[$card['status']]; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Systemgruppen mit Karten im Grid-Layout -->
<h2 class="h4 mb-4">Systemstatus</h2>
<div class="row g-4 mb-5">
    <?php foreach ($hostGroups as $group): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0"><?php echo h($group['name']); ?></h3>
                    <span class="badge rounded-pill
                        <?php
                        switch($groupStatus[$group['id']]) {
                            case 'operational':
                                echo 'badge-operational';
                                break;
                            case 'investigating':
                                echo 'badge-investigating';
                                break;
                            case 'progress':
                                echo 'badge-progress';
                                break;
                            case 'identified':
                                echo 'badge-identified';
                                break;
                            case 'monitoring':
                                echo 'badge-monitoring';
                                break;
                            case 'planned':
                                echo 'badge-planned';
                                break;
                            case 'completed':
                                echo 'badge-resolved'; // Gleiche Klasse wie resolved
                                break;
                            default:
                                echo 'badge-resolved';
                        }
                        ?>">
                        <?php echo $statusTexts[$groupStatus[$group['id']]] ?? $groupStatus[$group['id']]; ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- 24h Timeline als Balken -->
                    <div class="d-flex gap-1 mb-3 rounded overflow-hidden">
                        <?php for ($i = 23; $i >= 0; $i--): ?>
                            <?php
                            $timelineSlot = $timelineData[$group['id']][$i];
                            $statusClass = match($timelineSlot['status']) {
                                'operational' => 'status-operational',
                                'investigating' => 'status-investigating',
                                'progress' => 'status-progress',
                                'identified' => 'status-identified',
                                'monitoring' => 'status-monitoring',
                                'completed' => 'status-resolved', // Gleiche Klasse wie resolved
                                default => 'status-resolved'
                            };
                            ?>
                            <div class="flex-grow-1 timeline-bar <?php echo $statusClass; ?>"
                                 <?php if ($timelineSlot['incident']): ?>
                                 title="<?php echo h($timelineSlot['incident']['title']); ?>"
                                 <?php endif; ?>></div>
                        <?php endfor; ?>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <span>24h</span>
                        <span>Jetzt</span>
                    </div>
                    <?php
                    // Hosts für diese Gruppe
                    $groupHosts = array_filter($hosts, function($host) use ($group) {
                        return $host['group_id'] == $group['id'];
                    });
                    
                    if (!empty($groupHosts)):
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($groupHosts as $host): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                    <div class="d-flex align-items-center">
                                        <div class="status-indicator me-2
                                            <?php
                                            switch($hostStatus[$host['id']]) {
                                                case 'operational':
                                                    echo 'bg-success';
                                                    break;
                                                case 'investigating':
                                                case 'identified':
                                                    echo 'bg-warning';
                                                    break;
                                                case 'progress':
                                                case 'monitoring':
                                                    echo 'bg-info';
                                                    break;
                                                case 'planned':
                                                    echo 'bg-primary';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-secondary'; // Gleiche Farbe wie resolved
                                                    break;
                                                default:
                                                    echo 'bg-secondary';
                                            }
                                            ?>"></div>
                                        <span class="fw-medium"><?php echo h($host['name']); ?></span>
                                    </div>
                                    <span class="badge rounded-pill
                                        <?php
                                        switch($hostStatus[$host['id']]) {
                                            case 'operational':
                                                echo 'badge-operational';
                                                break;
                                            case 'investigating':
                                                echo 'badge-investigating';
                                                break;
                                            case 'progress':
                                                echo 'badge-progress';
                                                break;
                                            case 'identified':
                                                echo 'badge-identified';
                                                break;
                                            case 'monitoring':
                                                echo 'badge-monitoring';
                                                break;
                                            case 'planned':
                                                echo 'badge-planned';
                                                break;
                                            case 'completed':
                                                echo 'badge-resolved'; // Gleiche Klasse wie resolved
                                                break;
                                            default:
                                                echo 'badge-resolved';
                                            }
                                        ?>">
                                        <?php echo $statusTexts[$hostStatus[$host['id']]] ?? $hostStatus[$host['id']]; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Aktuelle Vorfälle in Tabs -->
<div class="card shadow-sm mb-5">
    <div class="card-header bg-transparent">
        <ul class="nav nav-tabs card-header-tabs" id="incidentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-incidents" type="button" role="tab" aria-controls="active-incidents" aria-selected="true">
                    Aktuelle Vorfälle
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="planned-tab" data-bs-toggle="tab" data-bs-target="#planned-incidents" type="button" role="tab" aria-controls="planned-incidents" aria-selected="false">
                    Geplante Wartungen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resolved-tab" data-bs-toggle="tab" data-bs-target="#resolved-incidents" type="button" role="tab" aria-controls="resolved-incidents" aria-selected="false">
                    Behobene Vorfälle
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="incidentTabsContent">
            <!-- Aktuelle Vorfälle -->
            <div class="tab-pane fade show active" id="active-incidents" role="tabpanel" aria-labelledby="active-tab">
                <?php
                $activeIncidents = array_filter($incidents, function($incident) {
                    return $incident['status'] != 'resolved' && $incident['status'] != 'completed' && $incident['status'] != 'planned';
                });
                
                if (empty($activeIncidents)):
                ?>
                    <div class="text-center py-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                        <p class="mb-0">Keine aktiven Vorfälle. Alle Systeme funktionieren normal.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeIncidents as $incident): ?>
                        <?php renderIncident($incident); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Geplante Wartungen -->
            <div class="tab-pane fade" id="planned-incidents" role="tabpanel" aria-labelledby="planned-tab">
                <?php
                $plannedIncidents = array_filter($incidents, function($incident) {
                    return $incident['status'] == 'planned';
                });
                
                if (empty($plannedIncidents)):
                ?>
                    <div class="text-center py-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-calendar-event text-primary fs-4"></i>
                        </div>
                        <p class="mb-0">Keine geplanten Wartungen in den nächsten Tagen.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($plannedIncidents as $incident): ?>
                        <?php renderIncident($incident); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Behobene Vorfälle -->
            <div class="tab-pane fade" id="resolved-incidents" role="tabpanel" aria-labelledby="resolved-tab">
                <?php
                $resolvedIncidents = array_filter($incidents, function($incident) {
                    return $incident['status'] == 'resolved' || $incident['status'] == 'completed';
                });
                
                if (empty($resolvedIncidents)):
                ?>
                    <div class="text-center py-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-archive text-secondary fs-4"></i>
                        </div>
                        <p class="mb-0">Keine behobenen Vorfälle in den letzten <?php echo $incidentDays; ?> Tagen.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($resolvedIncidents, 0, 5) as $incident): ?>
                        <?php renderIncident($incident); ?>
                    <?php endforeach; ?>
                    
                    <?php if (count($resolvedIncidents) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="index.php?page=incidents" class="btn btn-outline-primary">
                                Alle behobenen Vorfälle anzeigen
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Benachrichtigungen -->
<div id="subscribe-section" class="card shadow-sm mb-5">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="h3 mb-2">Benachrichtigungen erhalten</h2>
                <p class="text-muted mb-0">Erhalten Sie Benachrichtigungen über neue Störungen und Updates per E-Mail. Wir informieren Sie nur bei relevanten Änderungen.</p>
            </div>
            <div class="col-lg-6">
                <?php if (!empty($subscribeMessage)): ?>
                    <div class="alert alert-success mb-4">
                        <?php echo h($subscribeMessage); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($subscribeError)): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo h($subscribeError); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="d-flex gap-2">
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Ihre E-Mail-Adresse" 
                           required>
                    <button type="submit" 
                            name="subscribe" 
                            class="btn btn-primary">
                        Anmelden
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
}
?>