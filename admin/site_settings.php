<?php
session_start();
require_once('../includes/functions.php');
require_once('../includes/site_functions.php');

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Aktuelle Einstellungen abrufen
$siteSettings = getSiteSettings();

// Formular wurde abgeschickt
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Einstellungen aktualisieren
    $newSettings = [
        'site_title' => $_POST['site_title'] ?? '',
        'company_name' => $_POST['company_name'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_email' => $_POST['company_email'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'custom_css' => $_POST['custom_css'] ?? '',
        'custom_footer_text' => $_POST['custom_footer_text'] ?? '',
        'imprint_url' => $_POST['imprint_url'] ?? '',
        'privacy_url' => $_POST['privacy_url'] ?? '',
        'logo_path' => $siteSettings['logo_path'] // Standardmäßig den aktuellen Pfad beibehalten
    ];
    
    // Logo-Upload verarbeiten
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logoPath = saveLogo($_FILES['logo']);
        
        if ($logoPath) {
            $newSettings['logo_path'] = $logoPath;
        } else {
            $error = 'Das Logo konnte nicht hochgeladen werden. Bitte überprüfen Sie, ob es sich um ein gültiges Bild handelt.';
        }
    }
    
    // Einstellungen speichern, wenn kein Fehler aufgetreten ist
    if (empty($error)) {
        if (updateSiteSettings($newSettings)) {
            $message = 'Die Einstellungen wurden erfolgreich aktualisiert.';
            $siteSettings = $newSettings; // Aktualisierte Einstellungen anzeigen
        } else {
            $error = 'Die Einstellungen konnten nicht gespeichert werden.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website-Einstellungen - Statuspage Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <main class="admin-content">
            <h1>Website-Einstellungen</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo h($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-section">
                    <h2>Allgemeine Einstellungen</h2>
                    
                    <div class="form-group">
                        <label for="site_title">Seitentitel</label>
                        <input type="text" id="site_title" name="site_title" value="<?php echo h($siteSettings['site_title']); ?>" required>
                        <small>Wird im Browser-Tab und als Hauptüberschrift angezeigt.</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Firmeninformationen</h2>
                    
                    <div class="form-group">
                        <label for="company_name">Firmenname</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo h($siteSettings['company_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_address">Adresse</label>
                        <input type="text" id="company_address" name="company_address" value="<?php echo h($siteSettings['company_address']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_email">E-Mail</label>
                        <input type="email" id="company_email" name="company_email" value="<?php echo h($siteSettings['company_email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_phone">Telefon</label>
                        <input type="text" id="company_phone" name="company_phone" value="<?php echo h($siteSettings['company_phone']); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Design-Einstellungen</h2>
                    
                    <div class="form-group">
                        <label for="logo">Logo</label>
                        <?php if (!empty($siteSettings['logo_path'])): ?>
                            <div class="current-logo">
                                <img src="../<?php echo h($siteSettings['logo_path']); ?>" alt="Aktuelles Logo" style="max-height: 100px;">
                                <p>Aktuelles Logo</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="logo" name="logo" accept="image/*">
                        <small>Empfohlene Größe: 200x50 Pixel. Unterstützte Formate: JPG, PNG, GIF, SVG.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="custom_css">Benutzerdefiniertes CSS</label>
                        <textarea id="custom_css" name="custom_css" rows="8"><?php echo h($siteSettings['custom_css']); ?></textarea>
                        <small>Fügen Sie hier benutzerdefiniertes CSS hinzu, um das Aussehen der Statusseite anzupassen.</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Footer-Einstellungen</h2>
                    
                    <div class="form-group">
                        <label for="custom_footer_text">Benutzerdefinierter Footer-Text</label>
                        <textarea id="custom_footer_text" name="custom_footer_text" rows="4"><?php echo h($siteSettings['custom_footer_text']); ?></textarea>
                        <small>Dieser Text wird im Footer der Statusseite angezeigt.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="imprint_url">URL zum Impressum</label>
                        <input type="url" id="imprint_url" name="imprint_url" value="<?php echo h($siteSettings['imprint_url']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="privacy_url">URL zur Datenschutzerklärung</label>
                        <input type="url" id="privacy_url" name="privacy_url" value="<?php echo h($siteSettings['privacy_url']); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>