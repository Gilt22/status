<?php
// Hole aktuelle Störungen (ohne Wartungsarbeiten)
$activeIncidents = getIncidents(null, INCIDENTS_DAYS, false);

// Hole Wartungsarbeiten für die letzten 7 Tage und die nächsten 30 Tage
$maintenanceIncidents = getMaintenanceByDateRange(7, 30);
$hostGroups = getAllHostGroups();
$hosts = getHosts();

// Status-Logik wie gehabt...
$groupStatus = [];
$hostStatus = [];
$statusPriority = [
    'investigating' => 1,
    'identified' => 2,
    'monitoring' => 3,
    'planned' => 4,
    'resolved' => 5
];

// Berechne Status für Gruppen und Hosts...
foreach ($hostGroups as $group) {
    $groupStatus[$group['id']] = 'operational';
    foreach ($activeIncidents as $incident) {
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
    foreach ($activeIncidents as $incident) {
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
foreach ($activeIncidents as $incident) {
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
    'identified' => 'Problem erkannt',
    'monitoring' => 'Wird überwacht',
    'resolved' => 'Behoben'
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
// Vorfälle in Kategorien einteilen
$activeIncidents = array_filter($activeIncidents, function($incident) {
    return !empty($incident);
});

// Nach Datum sortieren (neueste zuerst)
usort($activeIncidents, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Vorfälle in Kategorien einteilen
$currentIncidents = [];
$plannedMaintenance = [];
$recentlyResolved = [];

foreach ($activeIncidents as $incident) {
    if ($incident['status'] === 'planned') {
        $plannedMaintenance[] = $incident;
    } elseif ($incident['status'] === 'resolved') {
        $recentlyResolved[] = $incident;
    } else {
        $currentIncidents[] = $incident;
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
                        case 'investigating':
                            echo 'badge-investigating';
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
                        case 'resolved':
                            echo 'badge-resolved';
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
                    $plannedStart = isset($incident['planned_start']) ? strtotime($incident['planned_start']) : null;
                    $plannedEnd = isset($incident['planned_end']) ? strtotime($incident['planned_end']) : null;
                    ?>
                    <?php if ($plannedStart): ?>
                        <p class="mb-1">
                            <i class="bi bi-calendar me-1"></i>
                            Geplant für: <?php echo date('d.m.Y H:i', $plannedStart); ?>
                            <?php if ($plannedEnd): ?>
                                - <?php echo date('d.m.Y H:i', $plannedEnd); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <i class="bi bi-clock me-1"></i>
                        Erwartete Dauer: <?php echo h($incident['expected_duration'] ?? 'Nicht angegeben'); ?>
                    </p>
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
?>

<?php if (!empty($currentIncidents)): ?>
    <div class="mt-5">
        <h2 class="h4 mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
            Aktuelle Störungen
        </h2>
        <?php foreach ($currentIncidents as $incident): ?>
            <?php renderIncident($incident); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($maintenanceIncidents)): ?>
    <div class="mt-5">
        <h2 class="h4 mb-4 d-flex align-items-center">
            <i class="bi bi-tools text-purple me-2"></i>
            Wartungsarbeiten
        </h2>
        
        <?php
        // Gruppiere Wartungsarbeiten nach Datum
        $maintenanceByDate = [];
        foreach ($maintenanceIncidents as $incident) {
            $date = date('Y-m-d', strtotime($incident['scheduled_start']));
            if (!isset($maintenanceByDate[$date])) {
                $maintenanceByDate[$date] = [];
            }
            $maintenanceByDate[$date][] = $incident;
        }
        
        // Sortiere nach Datum
        ksort($maintenanceByDate);
        ?>
        
        <?php foreach ($maintenanceByDate as $date => $incidents): ?>
            <h3 class="h5 mb-3">
                <?php
                $dateTime = strtotime($date);
                $today = strtotime('today');
                $tomorrow = strtotime('tomorrow');
                $yesterday = strtotime('yesterday');
                
                if ($dateTime == $today) {
                    echo 'Heute';
                } elseif ($dateTime == $tomorrow) {
                    echo 'Morgen';
                } elseif ($dateTime == $yesterday) {
                    echo 'Gestern';
                } else {
                    echo date('d.m.Y', $dateTime);
                }
                ?>
            </h3>
            <?php foreach ($incidents as $incident): ?>
                <?php renderIncident($incident); ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($recentlyResolved)): ?>
    <div class="mt-5">
        <h2 class="h4 mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            Kürzlich behobene Störungen
        </h2>
        <?php foreach ($recentlyResolved as $incident): ?>
            <?php renderIncident($incident); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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