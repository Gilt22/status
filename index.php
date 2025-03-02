<?php
require_once('includes/functions.php');
require_once('includes/site_functions.php');

$siteSettings = getSiteSettings();
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
?>
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($siteSettings['site_title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        status: {
                            operational: '#10B981',
                            investigating: '#F59E0B',
                            identified: '#EF4444',
                            monitoring: '#3B82F6',
                            resolved: '#6B7280'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <header class="text-center mb-12">
            <?php if (!empty($siteSettings['logo_path'])): ?>
                <div class="mb-6">
                    <img src="<?php echo h($siteSettings['logo_path']); ?>" 
                         alt="<?php echo h($siteSettings['company_name']); ?>" 
                         class="h-12 mx-auto">
                </div>
            <?php endif; ?>
            <h1 class="text-4xl font-bold text-gray-900 mb-2"><?php echo h($siteSettings['site_title']); ?></h1>
            <p class="text-lg text-gray-600">Aktuelle Informationen zum Status unserer Systeme</p>
        </header>

        <div class="mb-12">
            <?php
            $allOperational = true;
            foreach ($hostGroups as $group) {
                if ($groupStatus[$group['id']] != 'operational') {
                    $allOperational = false;
                    break;
                }
            }
            ?>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4
                           <?php echo $allOperational ? 'bg-green-100' : 'bg-yellow-100'; ?>">
                    <span class="text-2xl">
                        <?php echo $allOperational ? '✓' : '!'; ?>
                    </span>
                </div>
                <h2 class="text-2xl font-semibold text-gray-900">
                    <?php echo $allOperational ? 'Alle Systeme funktionieren' : 'Es gibt aktuelle Störungen'; ?>
                </h2>
            </div>
        </div>

        <div class="space-y-6">
            <?php foreach ($hostGroups as $group): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo h($group['name']); ?></h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                <?php
                                switch($groupStatus[$group['id']]) {
                                    case 'operational':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'investigating':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'identified':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    case 'monitoring':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php
                                $statusTexts = [
                                    'operational' => 'Betriebsbereit',
                                    'planned' => 'Geplante Wartung',
                                    'investigating' => 'Wird untersucht',
                                    'identified' => 'Problem erkannt',
                                    'monitoring' => 'Wird überwacht'
                                ];
                                echo $statusTexts[$groupStatus[$group['id']]] ?? $groupStatus[$group['id']];
                                ?>
                            </span>
                        </div>

                        <!-- 24h Timeline -->
                        <div class="flex gap-px h-2 mb-6 bg-gray-100 rounded overflow-hidden">
                            <?php for ($i = 23; $i >= 0; $i--): ?>
                                <?php
                                $timelineSlot = $timelineData[$group['id']][$i];
                                $statusClass = match($timelineSlot['status']) {
                                    'operational' => 'bg-green-400',
                                    'investigating' => 'bg-yellow-400',
                                    'identified' => 'bg-red-400',
                                    'monitoring' => 'bg-blue-400',
                                    default => 'bg-gray-400'
                                };
                                ?>
                                <div class="flex-1 <?php echo $statusClass; ?> hover:opacity-75 transition-opacity"
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
                            <div class="space-y-2">
                                <?php foreach ($groupHosts as $host): ?>
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition">
                                        <span class="text-sm font-medium text-gray-900"><?php echo h($host['name']); ?></span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php
                                            switch($hostStatus[$host['id']]) {
                                                case 'operational':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'investigating':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'identified':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'monitoring':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
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
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        <a href="incident.php?id=<?php echo $incident['id']; ?>" 
                           class="text-gray-900 hover:text-gray-600 transition">
                            <?php echo h($incident['title']); ?>
                        </a>
                    </h3>
                    <span class="px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap
                        <?php
                        switch($incident['status']) {
                            case 'investigating':
                                echo 'bg-yellow-100 text-yellow-800';
                                break;
                            case 'identified':
                                echo 'bg-red-100 text-red-800';
                                break;
                            case 'monitoring':
                                echo 'bg-blue-100 text-blue-800';
                                break;
                            case 'planned':
                                echo 'bg-purple-100 text-purple-800';
                                break;
                            case 'resolved':
                                echo 'bg-gray-100 text-gray-800';
                                break;
                            default:
                                echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?php echo $statusTexts[$incident['status']] ?? $incident['status']; ?>
                    </span>
                </div>
                
                <div class="text-sm text-gray-500 space-y-1 mb-4">
                    <?php if ($incident['status'] === 'planned'): ?>
                        <?php
                        $plannedStart = isset($incident['planned_start']) ? strtotime($incident['planned_start']) : null;
                        $plannedEnd = isset($incident['planned_end']) ? strtotime($incident['planned_end']) : null;
                        ?>
                        <?php if ($plannedStart): ?>
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Geplant für: <?php echo date('d.m.Y H:i', $plannedStart); ?>
                                <?php if ($plannedEnd): ?>
                                    - <?php echo date('d.m.Y H:i', $plannedEnd); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Erwartete Dauer: <?php echo h($incident['expected_duration'] ?? 'Nicht angegeben'); ?>
                        </p>
                    <?php else: ?>
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Gemeldet: <?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?>
                        </p>
                        <?php if ($incident['status'] === 'resolved' && $incident['resolved_at']): ?>
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M5 13l4 4L19 7"/>
                                </svg>
                                Behoben: <?php echo date('d.m.Y H:i', strtotime($incident['resolved_at'])); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-gray-600 mb-4">
                    <?php echo nl2br(h($incident['description'])); ?>
                </div>
                
                <a href="incident.php?id=<?php echo $incident['id']; ?>" 
                   class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
                    Details anzeigen
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php
        }
        ?>

        <?php if (!empty($currentIncidents)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                    <span class="flex items-center">
                        <svg class="w-6 h-6 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Aktuelle Störungen
                    </span>
                </h2>
                <div class="space-y-4">
                    <?php foreach ($currentIncidents as $incident): ?>
                        <?php renderIncident($incident); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($maintenanceIncidents)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                    <span class="flex items-center">
                        <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Wartungsarbeiten
                    </span>
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
                
                <div class="space-y-8">
                    <?php foreach ($maintenanceByDate as $date => $incidents): ?>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
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
                            <div class="space-y-4">
                                <?php foreach ($incidents as $incident): ?>
                                    <?php renderIncident($incident); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($recentlyResolved)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                    <span class="flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Kürzlich behobene Störungen
                    </span>
                </h2>
                <div class="space-y-4">
                    <?php foreach ($recentlyResolved as $incident): ?>
                        <?php renderIncident($incident); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-12 bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
            <h2 class="text-2xl font-semibold text-gray-900 mb-2">Benachrichtigungen erhalten</h2>
            <p class="text-gray-600 mb-6">Erhalten Sie Benachrichtigungen über neue Störungen und Updates per E-Mail.</p>
            
            <?php if (!empty($subscribeMessage)): ?>
                <div class="mb-6 p-4 bg-green-50 rounded-lg">
                    <p class="text-green-800"><?php echo h($subscribeMessage); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($subscribeError)): ?>
                <div class="mb-6 p-4 bg-red-50 rounded-lg">
                    <p class="text-red-800"><?php echo h($subscribeError); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" class="max-w-md mx-auto">
                <div class="flex gap-3">
                    <input type="email" 
                           name="email" 
                           class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           placeholder="Ihre E-Mail-Adresse" 
                           required>
                    <button type="submit" 
                            name="subscribe" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Anmelden
                    </button>
                </div>
            </form>
        </div>

        <footer class="mt-12 text-center text-sm text-gray-500">
            <?php if (!empty($siteSettings['custom_footer_text'])): ?>
                <div class="mb-4">
                    <?php echo nl2br(h($siteSettings['custom_footer_text'])); ?>
                </div>
            <?php endif; ?>
            
            <p>
                &copy; <?php echo date('Y'); ?> - <?php echo h($siteSettings['company_name'] ?: 'Statuspage'); ?>
                <?php if (!empty($siteSettings['imprint_url'])): ?>
                    | <a href="<?php echo h($siteSettings['imprint_url']); ?>" class="text-gray-600 hover:text-gray-900">Impressum</a>
                <?php endif; ?>
                <?php if (!empty($siteSettings['privacy_url'])): ?>
                    | <a href="<?php echo h($siteSettings['privacy_url']); ?>" class="text-gray-600 hover:text-gray-900">Datenschutz</a>
                <?php endif; ?>
                | <a href="admin/login.php" class="text-gray-600 hover:text-gray-900">Admin Login</a>
            </p>
        </footer>
    </div>
</body>
</html>