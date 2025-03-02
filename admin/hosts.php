<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Hosts abrufen
$hosts = getHosts();

// Host löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if (deleteHost($id)) {
        header('Location: hosts.php?deleted=1');
        exit;
    }
}

$message = '';
if (isset($_GET['created'])) {
    $message = 'Host wurde erfolgreich erstellt.';
} elseif (isset($_GET['updated'])) {
    $message = 'Host wurde erfolgreich aktualisiert.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Host wurde erfolgreich gelöscht.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hosts verwalten - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Hosts verwalten</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo h($message); ?></div>
            <?php endif; ?>
            
            <div class="form-actions mb-3">
                <a href="edit_host.php" class="btn btn-primary">Neuen Host erstellen</a>
            </div>
            
            <?php if (empty($hosts)): ?>
                <p>Keine Hosts vorhanden.</p>
            <?php else: ?>
                <div class="form-section">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Hostgruppe</th>
                                <th>Beschreibung</th>
                                <th>Erstellt am</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hosts as $host): ?>
                                <tr>
                                    <td><?php echo h($host['name']); ?></td>
                                    <td><?php echo h($host['group_name'] ?? 'Keine Gruppe'); ?></td>
                                    <td><?php echo h($host['description']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($host['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_host.php?id=<?php echo $host['id']; ?>" class="btn btn-sm">Bearbeiten</a>
                                        <a href="hosts.php?delete=<?php echo $host['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sind Sie sicher? Dies kann nicht rückgängig gemacht werden.')">Löschen</a>
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