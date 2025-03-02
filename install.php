<?php
session_start();

// Überprüfen ob die Installation bereits durchgeführt wurde
if (file_exists('install.lock')) {
    die('Die Installation wurde bereits durchgeführt. Aus Sicherheitsgründen wurde dieser Installer gesperrt.');
}

require_once('includes/functions.php');
require_once('includes/database.php');

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Installations-Schritte verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDbConnection();
    
    switch ($step) {
        case 1: // Site-Einstellungen speichern
            try {
                $stmt = $db->prepare("
                    UPDATE site_settings SET 
                    site_title = :site_title,
                    company_name = :company_name,
                    company_address = :company_address,
                    company_email = :company_email,
                    company_phone = :company_phone,
                    custom_footer_text = :custom_footer_text,
                    imprint_url = :imprint_url,
                    privacy_url = :privacy_url,
                    incident_days = :incident_days,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = 1
                ");
                
                $stmt->bindValue(':site_title', $_POST['site_title'], SQLITE3_TEXT);
                $stmt->bindValue(':company_name', $_POST['company_name'], SQLITE3_TEXT);
                $stmt->bindValue(':company_address', $_POST['company_address'], SQLITE3_TEXT);
                $stmt->bindValue(':company_email', $_POST['company_email'], SQLITE3_TEXT);
                $stmt->bindValue(':company_phone', $_POST['company_phone'], SQLITE3_TEXT);
                $stmt->bindValue(':custom_footer_text', $_POST['custom_footer_text'], SQLITE3_TEXT);
                $stmt->bindValue(':imprint_url', $_POST['imprint_url'], SQLITE3_TEXT);
                $stmt->bindValue(':privacy_url', $_POST['privacy_url'], SQLITE3_TEXT);
                $stmt->bindValue(':incident_days', $_POST['incident_days'], SQLITE3_TEXT);
                
                $stmt->execute();
                header('Location: install.php?step=2');
                exit;
            } catch (Exception $e) {
                $error = 'Fehler beim Speichern der Einstellungen: ' . $e->getMessage();
            }
            break;
            
        case 2: // Admin-Benutzer aktualisieren
            $password = $_POST['password'];
            $email = $_POST['email'];
            
            if (strlen($password) < 8) {
                $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
                break;
            }
            
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO admins (password, email) VALUES (:password, :email)");
                $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $stmt->execute();
                
                // Installation abschließen
                file_put_contents('install.lock', date('Y-m-d H:i:s'));
                $success = 'Installation erfolgreich abgeschlossen! <a href="admin/login.php">Zum Login</a>';
            } catch (Exception $e) {
                $error = 'Fehler beim Aktualisieren des Admin-Benutzers: ' . $e->getMessage();
            }
            break;
    }
    
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Statuspage</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .install-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .step {
            color: #666;
        }
        .step.active {
            color: #007bff;
            font-weight: bold;
        }
        .step.completed {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                1. Einstellungen
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                2. Admin-Benutzer
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>

            <?php if ($step == 1): ?>
                <h2>Website-Einstellungen</h2>
                <form method="post" class="admin-form">
                    <div class="form-group">
                        <label for="site_title">Seitentitel</label>
                        <input type="text" id="site_title" name="site_title" required>
                    </div>

                    <div class="form-group">
                        <label for="company_name">Firmenname</label>
                        <input type="text" id="company_name" name="company_name" required>
                    </div>

                    <div class="form-group">
                        <label for="company_address">Adresse</label>
                        <input type="text" id="company_address" name="company_address">
                    </div>

                    <div class="form-group">
                        <label for="company_email">E-Mail</label>
                        <input type="email" id="company_email" name="company_email" required>
                    </div>

                    <div class="form-group">
                        <label for="company_phone">Telefon</label>
                        <input type="text" id="company_phone" name="company_phone">
                    </div>

                    <div class="form-group">
                        <label for="incident_days">Anzahl der Tage für Vorfallsanzeige</label>
                        <input type="number" id="incident_days" name="incident_days" value="7" required>
                    </div>

                    <div class="form-group">
                        <label for="custom_footer_text">Footer-Text</label>
                        <textarea id="custom_footer_text" name="custom_footer_text" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="imprint_url">URL zum Impressum</label>
                        <input type="url" id="imprint_url" name="imprint_url">
                    </div>

                    <div class="form-group">
                        <label for="privacy_url">URL zur Datenschutzerklärung</label>
                        <input type="url" id="privacy_url" name="privacy_url">
                    </div>

                    <button type="submit" class="btn btn-primary">Weiter</button>
                </form>

            <?php elseif ($step == 2): ?>
                <h2>Admin-Benutzer anpassen</h2>
                <p>Ein Standard-Admin-Benutzer wurde bereits erstellt. Hier können Sie die Zugangsdaten ändern.</p>
                <form method="post" class="admin-form">
                    <div class="form-group">
                        <label for="email">E-Mail</label>
                        <input type="email" id="email" name="email" value="admin@example.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Neues Passwort</label>
                        <input type="password" id="password" name="password" required>
                        <small>Mindestens 8 Zeichen</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Installation abschließen</button>
                </form>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>