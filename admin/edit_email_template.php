<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$template = $id ? getEmailTemplate($id) : false;
$isEdit = $template !== false;

$error = '';
$name = $isEdit ? $template['name'] : '';
$subject = $isEdit ? $template['subject'] : '';
$body = $isEdit ? $template['body'] : '';
$description = $isEdit ? $template['description'] : '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $body = isset($_POST['body']) ? trim($_POST['body']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($name)) {
        $error = 'Bitte geben Sie einen Namen ein.';
    } elseif (empty($subject)) {
        $error = 'Bitte geben Sie einen Betreff ein.';
    } elseif (empty($body)) {
        $error = 'Bitte geben Sie einen Nachrichtentext ein.';
    } else {
        if ($isEdit) {
            // Template aktualisieren
            if (updateEmailTemplate($id, $name, $subject, $body, $description)) {
                header('Location: email_templates.php?updated=1');
                exit;
            } else {
                $error = 'Beim Aktualisieren des Templates ist ein Fehler aufgetreten.';
            }
        } else {
            // Neues Template erstellen
            if (createEmailTemplate($name, $subject, $body, $description)) {
                header('Location: email_templates.php?created=1');
                exit;
            } else {
                $error = 'Beim Erstellen des Templates ist ein Fehler aufgetreten.';
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
    <title><?php echo $isEdit ? 'E-Mail-Template bearbeiten' : 'Neues E-Mail-Template'; ?> - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
    .template-editor {
        margin-bottom: 20px;
    }
    
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .variable-inserter select {
        margin-right: 10px;
    }
    
    .preview-pane {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-top: 20px;
        white-space: pre-wrap;
    }
    
    .preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1><?php echo $isEdit ? 'E-Mail-Template bearbeiten' : 'Neues E-Mail-Template'; ?></h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <div class="form-section">
                    <h2>Template-Informationen</h2>
                    
                    <div class="form-group">
                        <label for="name">Template-Name</label>
                        <input type="text" id="name" name="name" value="<?php echo h($name); ?>" required>
                        <small>Ein eindeutiger Name zur Identifizierung des Templates (z.B. "subscription_confirmation").</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Beschreibung</label>
                        <input type="text" id="description" name="description" value="<?php echo h($description); ?>">
                        <small>Eine kurze Beschreibung, wofür dieses Template verwendet wird.</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>E-Mail-Inhalt</h2>
                    
                    <div class="form-group">
                        <label for="subject">E-Mail-Betreff</label>
                        <input type="text" id="subject" name="subject" value="<?php echo h($subject); ?>" required>
                        <small>Der Betreff der E-Mail. Sie können Variablen im Format {{variable_name}} verwenden.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="body">E-Mail-Text</label>
                        
                        <div class="template-editor">
                            <div class="editor-header">
                                <h3>Editor</h3>
                                <div class="variable-inserter">
                                    <select id="variable-selector">
                                        <option value="">-- Variable einfügen --</option>
                                        <optgroup label="Allgemeine Variablen">
                                            <option value="{{confirmation_link}}">Bestätigungslink</option>
                                            <option value="{{email}}">E-Mail</option>
                                            <option value="{{statuspage_url}}">Statuspage URL</option>
                                        </optgroup>
                                        <optgroup label="Vorfalls-Variablen">
                                            <option value="{{incident_title}}">Vorfallstitel</option>
                                            <option value="{{incident_status}}">Vorfallsstatus</option>
                                            <option value="{{incident_time}}">Vorfallszeit</option>
                                            <option value="{{incident_description}}">Vorfallsbeschreibung</option>
                                            <option value="{{affected_systems}}">Betroffene Systeme</option>
                                            <option value="{{incident_url}}">Vorfalls-URL</option>
                                        </optgroup>
                                        <optgroup label="Update-Variablen">
                                            <option value="{{update_time}}">Update-Zeit</option>
                                            <option value="{{update_message}}">Update-Nachricht</option>
                                        </optgroup>
                                    </select>
                                    <button type="button" id="insert-variable" class="btn btn-sm">Einfügen</button>
                                </div>
                            </div>
                            
                            <textarea id="body" name="body" rows="12" required><?php echo h($body); ?></textarea>
                            <small>Der Inhalt der E-Mail. Sie können Variablen im Format {{variable_name}} verwenden.</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Vorschau</h2>
                    
                    <div class="preview-container">
                        <div class="preview-header">
                            <button type="button" id="refresh-preview" class="btn btn-sm">Vorschau aktualisieren</button>
                        </div>
                        
                        <div id="preview" class="preview-pane"></div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Aktualisieren' : 'Erstellen'; ?></button>
                    <a href="email_templates.php" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variable einfügen
        var bodyEditor = document.getElementById('body');
        var variableSelector = document.getElementById('variable-selector');
        var insertButton = document.getElementById('insert-variable');
        var refreshButton = document.getElementById('refresh-preview');
        var previewPane = document.getElementById('preview');
        
        insertButton.addEventListener('click', function(e) {
            e.preventDefault();
            var variable = variableSelector.value;
            
            if (!variable) return;
            
            // Position im Editor ermitteln
            var startPos = bodyEditor.selectionStart;
            var endPos = bodyEditor.selectionEnd;
            
            // Variable an der Position einfügen
            bodyEditor.value = bodyEditor.value.substring(0, startPos) + variable + bodyEditor.value.substring(endPos);
            
            // Fokus zurück zum Editor
            bodyEditor.focus();
            bodyEditor.selectionStart = bodyEditor.selectionEnd = startPos + variable.length;
            
            // Dropdown zurücksetzen
            variableSelector.selectedIndex = 0;
            
            // Vorschau aktualisieren
            updatePreview();
        });
        
        // Vorschau aktualisieren
        refreshButton.addEventListener('click', function(e) {
            e.preventDefault();
            updatePreview();
        });
        
        function updatePreview() {
            var content = bodyEditor.value;
            
            // Platzhalter mit Beispielwerten ersetzen
            var sampleData = {
                'confirmation_link': 'https://example.com/confirm?token=abc123',
                'email': 'benutzer@example.com',
                'statuspage_url': 'https://example.com/status',
                'incident_title': 'Netzwerkstörung im Rechenzentrum',
                'incident_status': 'Untersuchung',
                'incident_time': '22.10.2023 15:30',
                'incident_description': 'Wir untersuchen derzeit eine Netzwerkstörung in unserem Hauptrechenzentrum.',
                'affected_systems': 'Gruppe: Webserver\nHost: api.example.com',
                'incident_url': 'https://example.com/status/incident/123',
                'update_time': '22.10.2023 16:15',
                'update_message': 'Wir haben die Ursache identifiziert und arbeiten an einer Lösung.'
            };
            
            for (var key in sampleData) {
                content = content.replace(new RegExp('{{' + key + '}}', 'g'), sampleData[key]);
            }
            
            // Platzhalter, die nicht ersetzt wurden, hervorheben
            content = content.replace(/{{[^}]+}}/g, function(match) {
                return '<span style="background-color: #ffeeba; padding: 0 3px; border-radius: 3px;">' + match + '</span>';
            });
            
            previewPane.innerHTML = content;
        }
        
        // Initial Vorschau aktualisieren
        updatePreview();
    });
    </script>
</body>
</html>