<?php
require_once('config.php');

/**
 * Stellt eine Verbindung zur SQLite-Datenbank her
 * @return SQLite3 Datenbankverbindung
 */
function getDbConnection() {
    try {
        $db = new SQLite3(DB_PATH);
        $db->enableExceptions(true);
        return $db;
    } catch (Exception $e) {
        die('Datenbankfehler: ' . $e->getMessage());
    }
}

/**
 * Initialisiert die Datenbank und erstellt die benötigten Tabellen, falls sie nicht existieren
 */
function initializeDatabase() {
    $db = getDbConnection();
    
    // Hostgruppen Tabelle
    $db->exec('CREATE TABLE IF NOT EXISTS host_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Hosts Tabelle
    $db->exec('CREATE TABLE IF NOT EXISTS hosts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_id INTEGER,
        name TEXT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES host_groups(id) ON DELETE CASCADE
    )');
    
    // Störungen Tabelle
    $db->exec('CREATE TABLE IF NOT EXISTS incidents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        type TEXT NOT NULL DEFAULT "incident", -- "incident", "performance", "maintenance", etc.
        status TEXT NOT NULL, -- "planned", "progress", "investigating", "identified", "monitoring", "resolved", "completed"
        scheduled_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        scheduled_end TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL
    )');
    
    // Verknüpfung zwischen Störungen und Hostgruppen
    $db->exec('CREATE TABLE IF NOT EXISTS incident_host_groups (
        incident_id INTEGER,
        group_id INTEGER,
        PRIMARY KEY (incident_id, group_id),
        FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
        FOREIGN KEY (group_id) REFERENCES host_groups(id) ON DELETE CASCADE
    )');
    
    // Verknüpfung zwischen Störungen und Hosts
    $db->exec('CREATE TABLE IF NOT EXISTS incident_hosts (
        incident_id INTEGER,
        host_id INTEGER,
        PRIMARY KEY (incident_id, host_id),
        FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
        FOREIGN KEY (host_id) REFERENCES hosts(id) ON DELETE CASCADE
    )');
    
    // Updates zu Störungen
    $db->exec('CREATE TABLE IF NOT EXISTS incident_updates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        incident_id INTEGER,
        message TEXT NOT NULL,
        status TEXT NOT NULL, -- "planned", "progress", "investigating", "identified", "monitoring", "resolved", "completed"
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE
    )');
    
    // Tabelle für E-Mail-Abonnenten
    $db->exec('CREATE TABLE IF NOT EXISTS subscribers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        token TEXT NOT NULL,
        confirmed INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Tabelle für Administratoren
    $db->exec('CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        password TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )');

    // Tabelle für E-Mail-Einstellungen
    $db->exec('CREATE TABLE IF NOT EXISTS email_settings (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        from_email TEXT NOT NULL,
        from_name TEXT NOT NULL,
        smtp_host TEXT,
        smtp_port INTEGER,
        smtp_username TEXT,
        smtp_password TEXT,
        smtp_encryption TEXT,
        use_smtp INTEGER DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Tabelle für E-Mail-Templates
    $db->exec('CREATE TABLE IF NOT EXISTS email_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Tabelle für Website-Einstellungen und Design
    $db->exec('CREATE TABLE IF NOT EXISTS site_settings (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        site_title TEXT NOT NULL DEFAULT "Statuspage",
        company_name TEXT,
        company_address TEXT,
        company_email TEXT,
        company_phone TEXT,
        logo_path TEXT,
        primary_color TEXT DEFAULT "#3498db",
        secondary_color TEXT DEFAULT "#2c3e50",
        accent_color TEXT DEFAULT "#f39c12",
        success_color TEXT DEFAULT "#2ecc71",
        danger_color TEXT DEFAULT "#e74c3c",
        imprint_url TEXT,
        privacy_url TEXT,
        custom_css TEXT,
        layout STRING DEFAULT "1",
        incident_days STRING DEFAULT "7",
        custom_footer_text TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Prüfen, ob Website-Einstellungen existieren, falls nicht, Standardwerte einfügen
    $result = $db->query('SELECT COUNT(*) as count FROM site_settings');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($count == 0) {
        $db->exec("INSERT INTO site_settings (id, site_title, company_name) 
                  VALUES (1, 'Statuspage', 'Unternehmen GmbH')");
    }

    // Prüfen, ob E-Mail-Einstellungen existieren, falls nicht, Standardwerte einfügen
    $result = $db->query('SELECT COUNT(*) as count FROM email_settings');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($count == 0) {
        $db->exec("INSERT INTO email_settings (id, from_email, from_name) 
                  VALUES (1, 'statuspage@example.com', 'Statuspage Benachrichtigungen')");
    }
    
    // Prüfen, ob E-Mail-Templates existieren, falls nicht, Standardtemplates einfügen
    $result = $db->query('SELECT COUNT(*) as count FROM email_templates');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($count == 0) {
        // Template für Abonnementbestätigung
        $db->exec("INSERT INTO email_templates (name, subject, body, description) 
                  VALUES ('subscription_confirmation', 
                          'Bitte bestätigen Sie Ihr Statuspage-Abonnement', 
                          'Hallo,\n\nvielen Dank für Ihr Interesse an unseren Statusupdates. Bitte klicken Sie auf den folgenden Link, um Ihr Abonnement zu bestätigen:\n\n{{confirmation_link}}\n\nWenn Sie diese E-Mail nicht angefordert haben, können Sie sie einfach ignorieren.\n\nMit freundlichen Grüßen,\nIhr Statuspage-Team',
                          'Dieses Template wird verwendet, wenn sich jemand für Benachrichtigungen anmeldet.')");
        
        // Template für neue Störung
        $db->exec("INSERT INTO email_templates (name, subject, body, description) 
                  VALUES ('new_incident', 
                          'Neue {{incident_type}}: {{incident_title}}', 
                          'Hallo,\n\nwir möchten Sie über eine neue {{incident_type}} informieren.\n\nTitel: {{incident_title}}\nStatus: {{incident_status}}\n{{time_info}}\n\nBeschreibung:\n{{incident_description}}\n\nBetroffene Systeme:\n{{affected_systems}}\n\nSie können den aktuellen Status hier einsehen:\n{{incident_url}}\n\nMit freundlichen Grüßen,\nIhr Statuspage-Team',
                          'Dieses Template wird verwendet, wenn eine neue Störung erstellt wird.')");
        
        // Template für Störungs-Update
        $db->exec("INSERT INTO email_templates (name, subject, body, description) 
                  VALUES ('incident_update', 
                          'Update zu Störung: {{incident_title}}', 
                          'Hallo,\n\nes gibt ein Update zu einer bestehenden Störung.\n\nTitel: {{incident_title}}\nNeuer Status: {{incident_status}}\nUpdate-Zeit: {{update_time}}\n\nUpdate-Nachricht:\n{{update_message}}\n\nSie können den aktuellen Status hier einsehen:\n{{incident_url}}\n\nMit freundlichen Grüßen,\nIhr Statuspage-Team',
                          'Dieses Template wird verwendet, wenn eine bestehende Störung aktualisiert wird.')");
        
        // Template für Abmeldung
        $db->exec("INSERT INTO email_templates (name, subject, body, description) 
                  VALUES ('unsubscribe', 
                          'Abmeldung von Statusbenachrichtigungen', 
                          'Hallo,\n\nSie haben sich erfolgreich von den Statusbenachrichtigungen abgemeldet.\n\nFalls Sie sich zu einem späteren Zeitpunkt wieder anmelden möchten, besuchen Sie einfach unsere Statusseite:\n{{statuspage_url}}\n\nMit freundlichen Grüßen,\nIhr Statuspage-Team',
                          'Dieses Template wird verwendet, wenn sich jemand von Benachrichtigungen abmeldet.')");
    }
    
    $db->close();
}

// Datenbank initialisieren
initializeDatabase();
?>