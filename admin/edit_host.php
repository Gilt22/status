<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$host = $id ? getHost($id) : false;
$isEdit = $host !== false;

$error = '';
$name = $isEdit ? $host['name'] : '';
$groupId = $isEdit ? $host['group_id'] : null;
$description = $isEdit ? $host['description'] : '';

// Alle Hostgruppen abrufen
$hostGroups = getAllHostGroups();

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $groupId = isset($_POST['group_id']) && $_POST['group_id'] ? (int)$_POST['group_id'] : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($name)) {
        $error = 'Bitte geben Sie einen Namen ein.';
    } else {
        if ($isEdit) {
            // Host aktualisieren
            if (updateHost($id, $name, $groupId, $description)) {
                header('Location: hosts.php?updated=1');
                exit;
            } else {
                $error = 'Beim Aktualisieren des Hosts ist ein Fehler aufgetreten.';
            }
        } else {
            // Neuen Host erstellen
            if (createHost($name, $groupId, $description)) {
                header('Location: hosts.php?created=1');
                exit;
            } else {
                $error = 'Beim Erstellen des Hosts ist ein Fehler aufgetreten.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Host bearbeiten' : 'Neuer Host'; ?> - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1><?php echo $isEdit ? 'Host bearbeiten' : 'Neuer Host'; ?></h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <div class="form-section">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo h($name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="group_id">Hostgruppe</label>
                        <select id="group_id" name="group_id">
                            <option value="">Keine Gruppe</option>
                            <?php foreach ($hostGroups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" <?php echo $groupId == $group['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Beschreibung</label>
                        <textarea id="description" name="description" rows="4"><?php echo h($description); ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Aktualisieren' : 'Erstellen'; ?></button>
                    <a href="hosts.php" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>