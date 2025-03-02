<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$emailSettings = getEmailSettings();
$success = '';
$error = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'from_email' => isset($_POST['from_email']) ? trim($_POST['from_email']) : '',
        'from_name' => isset($_POST['from_name']) ? trim($_POST['from_name']) : '',
        'use_smtp' => isset($_POST['use_smtp']) ? 1 : 0,
        'smtp_host' => isset($_POST['smtp_host']) ? trim($_POST['smtp_host']) : '',
        'smtp_port' => isset($_POST['smtp_port']) ? intval($_POST['smtp_port']) : null,
        'smtp_username' => isset($_POST['smtp_username']) ? trim($_POST['smtp_username']) : '',
        'smtp_password' => isset($_POST['smtp_password']) ? $_POST['smtp_password'] : $emailSettings['smtp_password'] ?? '',
        'smtp_encryption' => isset($_POST['smtp_encryption']) ? trim($_POST['smtp_encryption']) : ''
    ];
    
    if (empty($settings['from_email']) || !filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte geben Sie eine gültige Absender-E-Mail-Adresse ein.';
    } elseif (empty($settings['from_name'])) {
        $error = 'Bitte geben Sie einen Absendernamen ein.';
    } elseif ($settings['use_smtp'] && empty($settings['smtp_host'])) {
        $error = 'Wenn SMTP verwendet wird, muss ein SMTP-Server angegeben werden.';
    } else {
        if (updateEmailSettings($settings)) {
            $success = 'E-Mail-Einstellungen wurden erfolgreich aktualisiert.';
            $emailSettings = getEmailSettings(); // Neu laden
        } else {
            $error = 'Beim Aktualisieren der E-Mail-Einstellungen ist ein Fehler aufgetreten.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail-Einstellungen - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>E-Mail-Einstellungen</h1>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <div class="form-section">
                    <h2>Absender-Informationen</h2>
                    
                    <div class="form-group">
                        <label for="from_email">Absender-E-Mail</label>
                        <input type="email" id="from_email" name="from_email" 
                            value="<?php echo h($emailSettings['from_email']); ?>" required>
                        <small>Diese E-Mail-Adresse wird als Absender für alle Benachrichtigungen verwendet.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="from_name">Absender-Name</label>
                        <input type="text" id="from_name" name="from_name" 
                            value="<?php echo h($emailSettings['from_name']); ?>" required>
                        <small>Dieser Name wird als Absender für alle Benachrichtigungen angezeigt.</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>SMTP-Einstellungen</h2>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" id="use_smtp" name="use_smtp" value="1" 
                                    <?php echo isset($emailSettings['use_smtp']) && $emailSettings['use_smtp'] ? 'checked' : ''; ?>>
                                SMTP für den E-Mail-Versand verwenden
                            </label>
                        </div>
                        <small>Wenn aktiviert, werden E-Mails über einen SMTP-Server versendet. Andernfalls wird die PHP-Funktion mail() verwendet.</small>
                    </div>
                    
                    <div id="smtp-settings" style="<?php echo isset($emailSettings['use_smtp']) && $emailSettings['use_smtp'] ? '' : 'display:none;'; ?>">
                        <div class="form-group">
                            <label for="smtp_host">SMTP-Server</label>
                            <input type="text" id="smtp_host" name="smtp_host" 
                                value="<?php echo h($emailSettings['smtp_host'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_port">SMTP-Port</label>
                            <input type="number" id="smtp_port" name="smtp_port" 
                                value="<?php echo h($emailSettings['smtp_port'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_username">SMTP-Benutzername</label>
                            <input type="text" id="smtp_username" name="smtp_username" 
                                value="<?php echo h($emailSettings['smtp_username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_password">SMTP-Passwort</label>
                            <input type="password" id="smtp_password" name="smtp_password" 
                                placeholder="<?php echo !empty($emailSettings['smtp_password']) ? '********' : ''; ?>">
                            <small>Lassen Sie dieses Feld leer, um das bestehende Passwort beizubehalten.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_encryption">SMTP-Verschlüsselung</label>
                            <select id="smtp_encryption" name="smtp_encryption">
                                <option value="" <?php echo empty($emailSettings['smtp_encryption']) ? 'selected' : ''; ?>>Keine</option>
                                <option value="tls" <?php echo ($emailSettings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($emailSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var useSmtpCheckbox = document.getElementById('use_smtp');
        var smtpSettings = document.getElementById('smtp-settings');
        
        useSmtpCheckbox.addEventListener('change', function() {
            if (this.checked) {
                smtpSettings.style.display = 'block';
            } else {
                smtpSettings.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>