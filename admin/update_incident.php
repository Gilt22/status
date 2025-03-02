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

$error = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    if (empty($message)) {
        $error = 'Bitte geben Sie eine Update-Nachricht ein.';
    } elseif (empty($status)) {
        $error = 'Bitte wählen Sie einen Status aus.';
    } else {
        // Vorfallsstatus aktualisieren
        if (updateIncidentStatus($id, $message, $status)) {
            header('Location: view_incident.php?id=' . $id . '&updated=1');
            exit;
        } else {
            $error = 'Beim Aktualisieren des Vorfalls ist ein Fehler aufgetreten.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vorfall aktualisieren - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Vorfall aktualisieren</h1>
            
            <div class="form-section">
                <h2><?php echo h($incident['title']); ?></h2>
                <p>Aktueller Status: 
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
                </p>
                
                <p>Typ: 
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
                
                <?php if (!empty($incident['scheduled_start']) || !empty($incident['scheduled_end'])): ?>
                    <p>Zeitplan: 
                        <?php if (!empty($incident['scheduled_start'])): ?>
                            Von <?php echo date('d.m.Y H:i', strtotime($incident['scheduled_start'])); ?>
                        <?php endif; ?>
                        <?php if (!empty($incident['scheduled_end'])): ?>
                            bis <?php echo date('d.m.Y H:i', strtotime($incident['scheduled_end'])); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <div class="form-section">
                    <div class="form-group">
                        <label for="message">Update-Nachricht</label>
                        <textarea id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? h($_POST['message']) : ''; ?></textarea>
                        <small>Beschreiben Sie den aktuellen Status des Vorfalls und die getroffenen Maßnahmen.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Neuer Status</label>
                        <select id="status" name="status" required>
                            <option value="planned" <?php echo (isset($_POST['status']) && $_POST['status'] == 'planned') ? 'selected' : ''; ?>>Geplant</option>
                            <option value="investigating" <?php echo (isset($_POST['status']) && $_POST['status'] == 'investigating') ? 'selected' : ''; ?>>Untersuchung</option>
                            <option value="progress" <?php echo (isset($_POST['status']) && $_POST['status'] == 'progress') ? 'selected' : ''; ?>>In Bearbeitung</option>
                            <option value="identified" <?php echo (isset($_POST['status']) && $_POST['status'] == 'identified') ? 'selected' : ''; ?>>Identifiziert</option>
                            <option value="monitoring" <?php echo (isset($_POST['status']) && $_POST['status'] == 'monitoring') ? 'selected' : ''; ?>>Überwachung</option>
                            <option value="resolved" <?php echo (isset($_POST['status']) && $_POST['status'] == 'resolved') ? 'selected' : ''; ?>>Behoben</option>
                            <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') ? 'selected' : ''; ?>>Abgeschlossen</option>
                        </select>
                        <small>Wählen Sie den neuen Status des Vorfalls. Bei "Behoben" wird der Vorfall als abgeschlossen markiert.</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update hinzufügen</button>
                    <a href="view_incident.php?id=<?php echo $id; ?>" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>