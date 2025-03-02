<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$incident = getIncident($id);

if (!$incident) {
    header('Location: incidents.php');
    exit;
}

$message = '';
if (isset($_GET['updated'])) {
    $message = 'Vorfall wurde erfolgreich aktualisiert.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vorfallsdetails - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
    .timeline {
        position: relative;
        margin: 20px 0;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -30px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #ddd;
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #ddd;
    }
    
    .timeline-marker.status-investigating {
        background-color: #f39c12;
    }
    
    .timeline-marker.status-progress {
        background-color: #f39c12;
    }
    
    .timeline-marker.status-identified {
        background-color: #e67e22;
    }
    
    .timeline-marker.status-monitoring {
        background-color: #3498db;
    }
    
    .timeline-marker.status-resolved {
        background-color: #2ecc71;
    }
    
    .timeline-marker.status-planned {
        background-color: #3498db;
    }
    
    .timeline-marker.status-completed {
        background-color: #3498db;
    }
    
    .timeline-content {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .timeline-date {
        color: #666;
        font-size: 0.9rem;
    }
    
    .timeline-body {
        white-space: pre-line;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 10px;
        width: 2px;
        background-color: #ddd;
    }
    
    .incident-meta {
        margin-bottom: 20px;
        color: #666;
    }
    
    .incident-affected {
        margin-bottom: 20px;
    }
    
    .affected-section {
        margin-bottom: 15px;
    }
    
    .affected-section h5 {
        margin-bottom: 5px;
    }
    
    .affected-section ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Vorfallsdetails</h1>
                <div>
                    <a href="update_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-primary">Update hinzufügen</a>
                    <a href="incidents.php" class="btn btn-secondary">Zurück zur Übersicht</a>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo h($message); ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <div class="incident-header">
                    <h2><?php echo h($incident['title']); ?></h2>
                    <span class="status-badge status-<?php echo $incident['status']; ?>">
                        <?php
                        $statuses = [
                            'planned' => 'Geplant',
                            'investigating' => 'Untersuchung',
                            'progress' => 'In Bearbeitung',
                            'identified' => 'Identifiziert',
                            'monitoring' => 'Überwachung',
                            'resolved' => 'Behoben',
                            'completed' => 'Abgeschlossen'
                        ];
                        echo $statuses[$incident['status']] ?? $incident['status'];
                        ?>
                    </span>
                </div>
                
                <div class="incident-meta">
                    <p><strong>Typ:</strong> 
                        <?php 
                            $typeLabels = [
                                'incident' => 'Störung',
                                'maintenance' => 'Wartung',
                                'performance' => 'Leistungsproblem',
                                'security' => 'Sicherheitsvorfall'
                            ];
                            echo $typeLabels[$incident['type']] ?? $incident['type']; 
                        ?>
                    </p>
                    <p><strong>Erstellt am:</strong> <?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?></p>
                    
                    <?php if (!empty($incident['scheduled_start'])): ?>
                        <p><strong>Geplanter Start:</strong> <?php echo date('d.m.Y H:i', strtotime($incident['scheduled_start'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($incident['scheduled_end'])): ?>
                        <p><strong>Geplantes Ende:</strong> <?php echo date('d.m.Y H:i', strtotime($incident['scheduled_end'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($incident['status'] === 'resolved' && $incident['resolved_at']): ?>
                        <p><strong>Behoben am:</strong> <?php echo date('d.m.Y H:i', strtotime($incident['resolved_at'])); ?></p>
                    <?php endif; ?>

                    <?php if ($incident['status'] === 'completed' && $incident['resolved_at']): ?>
                        <p><strong>Abgeschlossen am:</strong> <?php echo date('d.m.Y H:i', strtotime($incident['resolved_at'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="incident-affected">
                    <h3>Betroffene Systeme</h3>
                    
                    <?php if (!empty($incident['affected_groups'])): ?>
                        <div class="affected-section">
                            <h4>Hostgruppen:</h4>
                            <ul>
                                <?php foreach ($incident['affected_groups'] as $group): ?>
                                    <li><?php echo h($group['name']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($incident['affected_hosts'])): ?>
                        <div class="affected-section">
                            <h4>Hosts:</h4>
                            <ul>
                                <?php foreach ($incident['affected_hosts'] as $host): ?>
                                    <li><?php echo h($host['name']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Updates</h2>
                
                <?php if (empty($incident['updates'])): ?>
                    <p>Keine Updates vorhanden.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($incident['updates'] as $update): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker status-<?php echo $update['status']; ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="status-badge status-<?php echo $update['status']; ?>">
                                            <?php echo $statuses[$update['status']] ?? $update['status']; ?>
                                        </span>
                                        <span class="timeline-date">
                                            <?php echo date('d.m.Y H:i', strtotime($update['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="timeline-body">
                                        <?php echo nl2br(h($update['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <a href="update_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-primary">Update hinzufügen</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>