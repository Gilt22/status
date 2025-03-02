<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Filter für Vorfälle
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Alle Vorfälle abrufen
if ($filter === 'planned') {
    $incidents = getIncidents('planned');
    $filterTitle = 'Geplante Vorfälle';
} elseif ($filter === 'active') {
    $incidents = getIncidentsByStatus(['investigating', 'identified', 'monitoring']);
    $filterTitle = 'Aktive Vorfälle';
} elseif ($filter === 'resolved') {
    $incidents = getIncidents('resolved');
    $filterTitle = 'Behobene Vorfälle';
} else {
    $incidents = getIncidents();
    $filterTitle = 'Alle Vorfälle';
}

// Nach Typ filtern, wenn angegeben
if ($type) {
    $incidents = array_filter($incidents, function($incident) use ($type) {
        return $incident['type'] === $type;
    });
    
    $typeLabels = [
        'incident' => 'Störungen',
        'maintenance' => 'Wartungen',
        'performance' => 'Leistungsprobleme',
        'security' => 'Sicherheitsvorfälle'
    ];
    
    $filterTitle = $typeLabels[$type] ?? $filterTitle;
}

$message = '';
if (isset($_GET['created'])) {
    $message = 'Vorfall wurde erfolgreich erstellt.';
} elseif (isset($_GET['updated'])) {
    $message = 'Vorfall wurde erfolgreich aktualisiert.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vorfälle verwalten - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Vorfälle verwalten</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo h($message); ?></div>
            <?php endif; ?>
            
            <div class="form-actions mb-3">
                <a href="create_incident.php" class="btn btn-primary">Neuen Vorfall erstellen</a>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle">Vorfallstyp wählen</button>
                    <div class="dropdown-menu">
                        <a href="create_incident.php?type=incident" class="dropdown-item">Störung</a>
                        <a href="create_incident.php?type=maintenance" class="dropdown-item">Wartung</a>
                        <a href="create_incident.php?type=performance" class="dropdown-item">Leistungsproblem</a>
                        <a href="create_incident.php?type=security" class="dropdown-item">Sicherheitsvorfall</a>
                    </div>
                </div>
            </div>
            
            <div class="filter-tabs mb-3">
                <a href="incidents.php" class="filter-tab <?php echo $filter === 'all' && !$type ? 'active' : ''; ?>">Alle</a>
                <a href="incidents.php?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">Aktive Vorfälle</a>
                <a href="incidents.php?filter=planned" class="filter-tab <?php echo $filter === 'planned' ? 'active' : ''; ?>">Geplante Vorfälle</a>
                <a href="incidents.php?filter=resolved" class="filter-tab <?php echo $filter === 'resolved' ? 'active' : ''; ?>">Behobene Vorfälle</a>
            </div>
            
            <div class="filter-tabs mb-3">
                <a href="incidents.php<?php echo $filter !== 'all' ? '?filter=' . $filter : ''; ?>" class="filter-tab <?php echo !$type ? 'active' : ''; ?>">Alle Typen</a>
                <a href="incidents.php?<?php echo $filter !== 'all' ? 'filter=' . $filter . '&' : ''; ?>type=incident" class="filter-tab <?php echo $type === 'incident' ? 'active' : ''; ?>">Störungen</a>
                <a href="incidents.php?<?php echo $filter !== 'all' ? 'filter=' . $filter . '&' : ''; ?>type=maintenance" class="filter-tab <?php echo $type === 'maintenance' ? 'active' : ''; ?>">Wartungen</a>
                <a href="incidents.php?<?php echo $filter !== 'all' ? 'filter=' . $filter . '&' : ''; ?>type=performance" class="filter-tab <?php echo $type === 'performance' ? 'active' : ''; ?>">Leistungsprobleme</a>
                <a href="incidents.php?<?php echo $filter !== 'all' ? 'filter=' . $filter . '&' : ''; ?>type=security" class="filter-tab <?php echo $type === 'security' ? 'active' : ''; ?>">Sicherheitsvorfälle</a>
            </div>
            
            <?php if (empty($incidents)): ?>
                <p>Keine <?php echo strtolower($filterTitle); ?> vorhanden.</p>
            <?php else: ?>
                <div class="form-section">
                    <h2><?php echo $filterTitle; ?></h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Typ</th>
                                <th>Status</th>
                                <th>Zeitplan</th>
                                <th>Erstellt am</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incidents as $incident): ?>
                                <tr>
                                    <td><?php echo h($incident['title']); ?></td>
                                    <td>
                                        <?php 
                                            $typeLabels = [
                                                'incident' => 'Störung',
                                                'maintenance' => 'Wartung',
                                                'performance' => 'Leistungsproblem',
                                                'security' => 'Sicherheitsvorfall'
                                            ];
                                            echo $typeLabels[$incident['type']] ?? $incident['type']; 
                                        ?>
                                    </td>
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
                                    <td>
                                        <?php if (!empty($incident['scheduled_start'])): ?>
                                            <div>Start: <?php echo date('d.m.Y H:i', strtotime($incident['scheduled_start'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($incident['scheduled_end'])): ?>
                                            <div>Ende: <?php echo date('d.m.Y H:i', strtotime($incident['scheduled_end'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (empty($incident['scheduled_start']) && empty($incident['scheduled_end'])): ?>
                                            <span class="text-muted">Nicht geplant</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?></td>
                                    <td>
                                        <a href="view_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-sm">Ansehen</a>
                                        <a href="update_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-sm">Update</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-toggle {
        cursor: pointer;
    }
    
    .dropdown-menu {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 200px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1;
        border-radius: 4px;
        padding: 0.5rem 0;
    }
    
    .dropdown:hover .dropdown-menu {
        display: block;
    }
    
    .dropdown-item {
        display: block;
        padding: 0.5rem 1rem;
        color: #333;
        text-decoration: none;
    }
    
    .dropdown-item:hover {
        background-color: #f5f5f5;
    }
    
    .text-muted {
        color: #6c757d;
    }
    </style>
</body>
</html>