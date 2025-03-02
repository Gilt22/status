<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Hostgruppen abrufen
$hostGroups = getAllHostGroups();

// Hostgruppe löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if (deleteHostGroup($id)) {
        header('Location: host_groups.php?deleted=1');
        exit;
    }
}

$message = '';
if (isset($_GET['created'])) {
    $message = 'Hostgruppe wurde erfolgreich erstellt.';
} elseif (isset($_GET['updated'])) {
    $message = 'Hostgruppe wurde erfolgreich aktualisiert.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Hostgruppe wurde erfolgreich gelöscht.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostgruppen verwalten - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Hostgruppen verwalten</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo h($message); ?></div>
            <?php endif; ?>
            
            <div class="form-actions mb-3">
                <a href="edit_host_group.php" class="btn btn-primary">Neue Hostgruppe erstellen</a>
            </div>
            
            <?php if (empty($hostGroups)): ?>
                <p>Keine Hostgruppen vorhanden.</p>
            <?php else: ?>
                <div class="form-section">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Beschreibung</th>
                                <th>Erstellt am</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hostGroups as $group): ?>
                                <tr>
                                    <td><?php echo h($group['name']); ?></td>
                                    <td><?php echo h($group['description']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($group['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_host_group.php?id=<?php echo $group['id']; ?>" class="btn btn-sm">Bearbeiten</a>
                                        <a href="host_groups.php?delete=<?php echo $group['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sind Sie sicher? Dies kann nicht rückgängig gemacht werden.')">Löschen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>