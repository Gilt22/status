<?php
$settings = getSiteSettings();

// Hole alle Vorfälle für den konfigurierten Zeitraum
$incidents = getIncidents(null, isset($settings['incident_days']) ? (int)$settings['incident_days'] : 7);
$hostGroups = getAllHostGroups();
$hosts = getHosts();

// Status-Logik wie gehabt...
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
        if ($incidentDetails && $incident['status'] != 'resolved') {
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
        if ($incidentDetails && $incident['status'] != 'resolved') {
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
        $endHour = $incident['resolved_at'] ? 
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
?>

<div class="mb-5">
    <?php
    $allOperational = true;
    foreach ($hostGroups as $group) {
        if ($groupStatus[$group['id']] != 'operational') {
            $allOperational = false;
            break;
        }
    }
    ?>
    
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
                <?php echo $allOperational ? 'Alle Systeme funktionieren' : 'Es gibt aktuelle Störungen'; ?>
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

<?php
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
                    <?php if ($incident['status'] === 'resolved' && $incident['resolved_at']): ?>
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

// Einstellungen holen
$settings = getSiteSettings();
$incidentDays = isset($settings['incident_days']) ? (int)$settings['incident_days'] : 7;

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
?>

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