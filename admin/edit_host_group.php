<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hostGroup = $id ? getHostGroup($id) : false;
$isEdit = $hostGroup !== false;

$error = '';
$name = $isEdit ? $hostGroup['name'] : '';
$description = $isEdit ? $hostGroup['description'] : '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($name)) {
        $error = 'Bitte geben Sie einen Namen ein.';
    } else {
        if ($isEdit) {
            // Hostgruppe aktualisieren
            if (updateHostGroup($id, $name, $description)) {
                header('Location: host_groups.php?updated=1');
                exit;
            } else {
                $error = 'Beim Aktualisieren der Hostgruppe ist ein Fehler aufgetreten.';
            }
        } else {
            // Neue Hostgruppe erstellen
            if (createHostGroup($name, $description)) {
                header('Location: host_groups.php?created=1');
                exit;
            } else {
                $error = 'Beim Erstellen der Hostgruppe ist ein Fehler aufgetreten.';
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
    <title><?php echo $isEdit ? 'Hostgruppe bearbeiten' : 'Neue Hostgruppe'; ?> - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1><?php echo $isEdit ? 'Hostgruppe bearbeiten' : 'Neue Hostgruppe'; ?></h1>
            
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
                        <label for="description">Beschreibung</label>
                        <textarea id="description" name="description" rows="4"><?php echo h($description); ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Aktualisieren' : 'Erstellen'; ?></button>
                    <a href="host_groups.php" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>