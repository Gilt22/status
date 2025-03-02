<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Aktuelle Vorfälle abrufen
$activeIncidents = getIncidents(null, 7); // Vorfälle der letzten 7 Tage
$plannedMaintenance = getIncidents('planned');
$activeIssues = getIncidentsByStatus(['investigating', 'identified', 'monitoring']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Dashboard</h1>
            
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="dashboard-card-title">
                        <h3>Hostgruppen</h3>
                        <a href="host_groups.php" class="btn btn-sm">Verwalten</a>
                    </div>
                    <div class="dashboard-card-count"><?php echo count(getAllHostGroups()); ?></div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-title">
                        <h3>Hosts</h3>
                        <a href="hosts.php" class="btn btn-sm">Verwalten</a>
                    </div>
                    <div class="dashboard-card-count"><?php echo count(getHosts()); ?></div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-title">
                        <h3>Aktive Vorfälle</h3>
                        <a href="incidents.php?filter=active" class="btn btn-sm">Verwalten</a>
                    </div>
                    <div class="dashboard-card-count"><?php echo count($activeIssues); ?></div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-title">
                        <h3>Geplante Wartungen</h3>
                        <a href="incidents.php?filter=planned" class="btn btn-sm">Verwalten</a>
                    </div>
                    <div class="dashboard-card-count"><?php echo count($plannedMaintenance); ?></div>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Aktuelle Vorfälle</h2>
                
                <?php if (empty($activeIssues)): ?>
                    <p>Keine aktiven Vorfälle vorhanden.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Status</th>
                                <th>Erstellt am</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeIssues as $incident): ?>
                                <tr>
                                    <td><?php echo h($incident['title']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $incident['status']; ?>">
                                            <?php
                                            $statuses = [
                                                'planned' => 'Geplant',
                                                'investigating' => 'Untersuchung',
                                                'identified' => 'Identifiziert',
                                                'monitoring' => 'Überwachung',
                                                'resolved' => 'Behoben'
                                            ];
                                            echo $statuses[$incident['status']] ?? $incident['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?></td>
                                    <td>
                                        <a href="view_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-sm">Details</a>
                                        <a href="update_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-sm">Update</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($plannedMaintenance)): ?>
            <div class="form-section">
                <h2>Geplante Wartungen</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Status</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plannedMaintenance as $maintenance): ?>
                            <tr>
                                <td><?php echo h($maintenance['title']); ?></td>
                                <td>
                                    <span class="status-badge status-planned">Geplant</span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($maintenance['created_at'])); ?></td>
                                <td>
                                    <a href="view_incident.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm">Details</a>
                                    <a href="update_incident.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm">Update</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <a href="create_incident.php?type=incident" class="btn btn-primary">Neuen Vorfall erstellen</a>
                <a href="create_incident.php?type=maintenance" class="btn btn-secondary">Geplante Wartung erstellen</a>
            </div>
        </main>
    </div>
</body>
</html>