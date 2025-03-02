<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vorfallstyp aus der URL abrufen (Standard: incident)
$incidentType = isset($_GET['type']) ? $_GET['type'] : 'incident';

// Hostgruppen und Hosts für die Auswahl abrufen
$hostGroups = getAllHostGroups();
$hosts = getHosts();

$error = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'incident';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $selectedGroups = isset($_POST['host_groups']) ? $_POST['host_groups'] : [];
    $selectedHosts = isset($_POST['hosts']) ? $_POST['hosts'] : [];
    
    // Zeitplanung
    $scheduledStart = null;
    if (!empty($_POST['scheduled_start_date']) && !empty($_POST['scheduled_start_time'])) {
        $scheduledStart = $_POST['scheduled_start_date'] . ' ' . $_POST['scheduled_start_time'] . ':00';
    }
    
    $scheduledEnd = null;
    if (!empty($_POST['scheduled_end_date']) && !empty($_POST['scheduled_end_time'])) {
        $scheduledEnd = $_POST['scheduled_end_date'] . ' ' . $_POST['scheduled_end_time'] . ':00';
    }
    
    if (empty($title)) {
        $error = 'Bitte geben Sie einen Titel ein.';
    } elseif (empty($status)) {
        $error = 'Bitte wählen Sie einen Status aus.';
    } elseif (empty($selectedGroups) && empty($selectedHosts)) {
        $error = 'Bitte wählen Sie mindestens eine Hostgruppe oder einen Host aus.';
    } elseif ($scheduledStart && $scheduledEnd && strtotime($scheduledStart) > strtotime($scheduledEnd)) {
        $error = 'Der geplante Endzeitpunkt muss nach dem Startzeitpunkt liegen.';
    } else {
        // Vorfall erstellen
        $incidentId = createIncident($title, $description, $status, $type, $selectedGroups, $selectedHosts, $scheduledStart, $scheduledEnd);
        
        if ($incidentId) {
            header('Location: incidents.php?created=1');
            exit;
        } else {
            $error = 'Beim Erstellen des Vorfalls ist ein Fehler aufgetreten.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuen Vorfall erstellen - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Neuen Vorfall erstellen</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <div class="form-section">
                    <h2>Allgemeine Informationen</h2>
                    
                    <div class="form-group">
                        <label for="title">Titel</label>
                        <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? h($_POST['title']) : ''; ?>" required>
                        <small>Beispiel: "Netzwerkprobleme im Rechenzentrum"</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Vorfallstyp</label>
                        <select id="type" name="type" required>
                            <option value="incident" <?php echo (isset($_POST['type']) && $_POST['type'] == 'incident' || $incidentType == 'incident') ? 'selected' : ''; ?>>Störung</option>
                            <option value="maintenance" <?php echo (isset($_POST['type']) && $_POST['type'] == 'maintenance' || $incidentType == 'maintenance') ? 'selected' : ''; ?>>Wartung</option>
                            <option value="performance" <?php echo (isset($_POST['type']) && $_POST['type'] == 'performance' || $incidentType == 'performance') ? 'selected' : ''; ?>>Leistungsprobleme</option>
                            <option value="security" <?php echo (isset($_POST['type']) && $_POST['type'] == 'security' || $incidentType == 'security') ? 'selected' : ''; ?>>Sicherheitsvorfall</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Beschreibung</label>
                        <textarea id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? h($_POST['description']) : ''; ?></textarea>
                        <small>Beschreiben Sie den Vorfall, die Auswirkungen und ggf. geplante Maßnahmen.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="">-- Status wählen --</option>
                            <option value="planned" <?php echo (isset($_POST['status']) && $_POST['status'] == 'planned') ? 'selected' : ''; ?>>Geplant</option>
                            <option value="investigating" <?php echo (isset($_POST['status']) && $_POST['status'] == 'investigating') ? 'selected' : ''; ?>>Untersuchung</option>
                            <option value="progress" <?php echo (isset($_POST['status']) && $_POST['status'] == 'progress') ? 'selected' : ''; ?>>In Bearbeitung</option>
                            <option value="identified" <?php echo (isset($_POST['status']) && $_POST['status'] == 'identified') ? 'selected' : ''; ?>>Identifiziert</option>
                            <option value="monitoring" <?php echo (isset($_POST['status']) && $_POST['status'] == 'monitoring') ? 'selected' : ''; ?>>Überwachung</option>
                            <option value="resolved" <?php echo (isset($_POST['status']) && $_POST['status'] == 'resolved') ? 'selected' : ''; ?>>Behoben</option>
                            <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') ? 'selected' : ''; ?>>Abgeschlossen</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Zeitplanung</h2>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="scheduled_start_date">Geplanter Start (Datum)</label>
                            <input type="date" id="scheduled_start_date" name="scheduled_start_date" value="<?php echo isset($_POST['scheduled_start_date']) ? h($_POST['scheduled_start_date']) : ''; ?>">
                        </div>
                        
                        <div class="form-group half">
                            <label for="scheduled_start_time">Geplanter Start (Uhrzeit)</label>
                            <input type="time" id="scheduled_start_time" name="scheduled_start_time" value="<?php echo isset($_POST['scheduled_start_time']) ? h($_POST['scheduled_start_time']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="scheduled_end_date">Geplantes Ende (Datum)</label>
                            <input type="date" id="scheduled_end_date" name="scheduled_end_date" value="<?php echo isset($_POST['scheduled_end_date']) ? h($_POST['scheduled_end_date']) : ''; ?>">
                        </div>
                        
                        <div class="form-group half">
                            <label for="scheduled_end_time">Geplantes Ende (Uhrzeit)</label>
                            <input type="time" id="scheduled_end_time" name="scheduled_end_time" value="<?php echo isset($_POST['scheduled_end_time']) ? h($_POST['scheduled_end_time']) : ''; ?>">
                        </div>
                    </div>
                    
                    <small>Hinweis: Lassen Sie die Felder leer, wenn keine Zeitplanung erforderlich ist. Bei ungeplanten Vorfällen wird der aktuelle Zeitpunkt verwendet.</small>
                </div>
                
                <div class="form-section">
                    <h2>Betroffene Systeme</h2>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Betroffene Hostgruppen</label>
                            <?php if (empty($hostGroups)): ?>
                                <p>Keine Hostgruppen vorhanden.</p>
                            <?php else: ?>
                                <div class="multi-select">
                                    <?php foreach ($hostGroups as $group): ?>
                                        <div class="checkbox-group">
                                            <label>
                                                <input type="checkbox" name="host_groups[]" value="<?php echo $group['id']; ?>" 
                                                    <?php echo (isset($_POST['host_groups']) && in_array($group['id'], $_POST['host_groups'])) ? 'checked' : ''; ?>>
                                                <?php echo h($group['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group half">
                            <label>Betroffene Hosts</label>
                            <?php if (empty($hosts)): ?>
                                <p>Keine Hosts vorhanden.</p>
                            <?php else: ?>
                                <div class="multi-select">
                                    <?php foreach ($hosts as $host): ?>
                                        <div class="checkbox-group">
                                            <label>
                                                <input type="checkbox" name="hosts[]" value="<?php echo $host['id']; ?>"
                                                    <?php echo (isset($_POST['hosts']) && in_array($host['id'], $_POST['hosts'])) ? 'checked' : ''; ?>>
                                                <?php echo h($host['name']); ?>
                                                <?php if (!empty($host['group_name'])): ?>
                                                    <small>(<?php echo h($host['group_name']); ?>)</small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Vorfall erstellen</button>
                    <a href="incidents.php" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Automatisch den Status auf "planned" setzen, wenn der Typ "maintenance" ist
        const typeSelect = document.getElementById('type');
        const statusSelect = document.getElementById('status');
        
        typeSelect.addEventListener('change', function() {
            if (this.value === 'maintenance') {
                statusSelect.value = 'planned';
            }
        });
        
        // Initial auslösen, falls "maintenance" vorausgewählt ist
        if (typeSelect.value === 'maintenance') {
            statusSelect.value = 'planned';
        }
    });
    </script>
</body>
</html>