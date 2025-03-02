<?php
require_once('database.php');

/**
 * Gibt die Website-Einstellungen zurück
 * @return array Website-Einstellungen
 */
function getSiteSettings() {
    $db = getDbConnection();
    $result = $db->query('SELECT * FROM site_settings WHERE id = 1');
    
    $settings = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    // Standardwerte zurückgeben, falls keine Einstellungen gefunden wurden
    if (!$settings) {
        return [
            'site_title' => 'Statuspage',
            'company_name' => 'Statuspage',
            'company_address' => '',
            'company_email' => '',
            'company_phone' => '',
            'logo_path' => '',
            'custom_css' => '',
            'custom_footer_text' => '',
            'imprint_url' => '',
            'privacy_url' => ''
        ];
    }
    
    return $settings;
}

/**
 * Aktualisiert die Website-Einstellungen
 * @param array $settings Die zu aktualisierenden Einstellungen
 * @return bool True bei Erfolg, false bei Fehler
 */
function updateSiteSettings($settings) {
    $db = getDbConnection();
    
    $stmt = $db->prepare('UPDATE site_settings SET 
                         site_title = :site_title, 
                         company_name = :company_name,
                         company_address = :company_address,
                         company_email = :company_email,
                         company_phone = :company_phone,
                         logo_path = :logo_path,
                         custom_css = :custom_css,
                         custom_footer_text = :custom_footer_text,
                         imprint_url = :imprint_url,
                         privacy_url = :privacy_url,
                         updated_at = CURRENT_TIMESTAMP 
                         WHERE id = 1');
                         
    $stmt->bindValue(':site_title', $settings['site_title'], SQLITE3_TEXT);
    $stmt->bindValue(':company_name', $settings['company_name'], SQLITE3_TEXT);
    $stmt->bindValue(':company_address', $settings['company_address'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':company_email', $settings['company_email'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':company_phone', $settings['company_phone'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':logo_path', $settings['logo_path'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':custom_css', $settings['custom_css'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':custom_footer_text', $settings['custom_footer_text'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':imprint_url', $settings['imprint_url'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':privacy_url', $settings['privacy_url'] ?? '', SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $changes = $db->changes();
    
    // Wenn keine Aktualisierung stattfand, Datensatz einfügen
    if ($changes == 0) {
        $stmt = $db->prepare('INSERT INTO site_settings (id, site_title, company_name, company_address, 
                             company_email, company_phone, logo_path, custom_css, custom_footer_text, 
                             imprint_url, privacy_url) 
                             VALUES (1, :site_title, :company_name, :company_address, :company_email, 
                             :company_phone, :logo_path, :custom_css, :custom_footer_text, 
                             :imprint_url, :privacy_url)');
                             
        $stmt->bindValue(':site_title', $settings['site_title'], SQLITE3_TEXT);
        $stmt->bindValue(':company_name', $settings['company_name'], SQLITE3_TEXT);
        $stmt->bindValue(':company_address', $settings['company_address'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':company_email', $settings['company_email'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':company_phone', $settings['company_phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':logo_path', $settings['logo_path'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':custom_css', $settings['custom_css'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':custom_footer_text', $settings['custom_footer_text'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':imprint_url', $settings['imprint_url'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':privacy_url', $settings['privacy_url'] ?? '', SQLITE3_TEXT);
        
        $result = $stmt->execute();
    }
    
    $db->close();
    return $result ? true : false;
}

/**
 * Speichert ein hochgeladenes Logo
 * @param array $file Hochgeladene Datei ($_FILES['logo'])
 * @return string|false Pfad zum gespeicherten Logo oder false bei Fehler
 */
function saveLogo($file) {
    // Überprüfen, ob eine Datei hochgeladen wurde
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return false;
    }
    
    // MIME-Typ der Datei ermitteln
    $mimeType = mime_content_type($file['tmp_name']);
    
    // Erlaubte MIME-Typen
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'image/webp' => 'webp'
    ];
    
    // Überprüfen, ob der MIME-Typ erlaubt ist
    if (!isset($allowedMimeTypes[$mimeType])) {
        return false;
    }
    
    // Dateiendung bestimmen
    $extension = $allowedMimeTypes[$mimeType];
    
    // Zielverzeichnis
    $uploadDir = __DIR__ . '/../assets/img/';
    
    // Sicherstellen, dass das Verzeichnis existiert
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Eindeutigen Dateinamen generieren
    $filename = 'logo_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Datei speichern
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'assets/img/' . $filename;
    }
    
    return false;
}
?>