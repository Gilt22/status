<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Alle Templates abrufen
$templates = getAllEmailTemplates();

$message = '';
if (isset($_GET['updated'])) {
    $message = 'E-Mail-Template wurde erfolgreich aktualisiert.';
} elseif (isset($_GET['created'])) {
    $message = 'E-Mail-Template wurde erfolgreich erstellt.';
} elseif (isset($_GET['deleted'])) {
    $message = 'E-Mail-Template wurde erfolgreich gelöscht.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail-Templates - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
    .template-variables {
        background-color: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .template-variables h3 {
        margin-top: 0;
    }
    
    .variable-groups {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .variable-group {
        flex: 1;
        min-width: 250px;
        padding: 0 10px;
        margin-bottom: 15px;
    }
    
    .variable-group h4 {
        margin-top: 0;
        font-size: 1.1rem;
    }
    
    .variable-group ul {
        padding-left: 20px;
    }
    
    .variable-group li {
        margin-bottom: 5px;
    }
    
    code {
        background-color: #f1f1f1;
        padding: 2px 4px;
        border-radius: 3px;
        font-family: monospace;
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>E-Mail-Templates</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo h($message); ?></div>
            <?php endif; ?>
            
            <div class="form-actions mb-3">
                <a href="edit_email_template.php" class="btn btn-primary">Neues Template erstellen</a>
            </div>
            
            <div class="form-section">
                <h2>Verfügbare Variablen</h2>
                <p>Die folgenden Variablen können in den Templates verwendet werden:</p>
                
                <div class="variable-groups">
                    <div class="variable-group">
                        <h4>Allgemeine Variablen</h4>
                        <ul>
                            <li><code>{{confirmation_link}}</code> - Link zur Bestätigung eines Abonnements</li>
                            <li><code>{{email}}</code> - E-Mail-Adresse des Abonnenten</li>
                            <li><code>{{statuspage_url}}</code> - URL zur Statuspage</li>
                        </ul>
                    </div>
                    
                    <div class="variable-group">
                        <h4>Störungs-Variablen</h4>
                        <ul>
                            <li><code>{{incident_title}}</code> - Titel der Störung</li>
                            <li><code>{{incident_status}}</code> - Status der Störung</li>
                            <li><code>{{incident_time}}</code> - Erstellungszeit der Störung</li>
                            <li><code>{{incident_description}}</code> - Beschreibung der Störung</li>
                            <li><code>{{affected_systems}}</code> - Liste der betroffenen Systeme</li>
                            <li><code>{{incident_url}}</code> - URL zu den Störungsdetails</li>
                        </ul>
                    </div>
                    
                    <div class="variable-group">
                        <h4>Update-Variablen</h4>
                        <ul>
                            <li><code>{{update_time}}</code> - Zeit des Updates</li>
                            <li><code>{{update_message}}</code> - Update-Nachricht</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if (empty($templates)): ?>
                <p>Keine E-Mail-Templates vorhanden.</p>
            <?php else: ?>
                <div class="form-section">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Betreff</th>
                                <th>Beschreibung</th>
                                <th>Letzte Aktualisierung</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo h($template['name']); ?></td>
                                    <td><?php echo h($template['subject']); ?></td>
                                    <td><?php echo h($template['description']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($template['updated_at'])); ?></td>
                                    <td>
                                        <a href="edit_email_template.php?id=<?php echo $template['id']; ?>" class="btn btn-sm">Bearbeiten</a>
                                        <?php if (count($templates) > 4): ?>
                                            <a href="delete_email_template.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sind Sie sicher? Dies kann nicht rückgängig gemacht werden.')">Löschen</a>
                                        <?php endif; ?>
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