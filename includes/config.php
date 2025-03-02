<?php
// Datenbankpfad
define('DB_PATH', __DIR__ . '/../database/statuspage.db');

// Überprüfen, ob das Datenbankverzeichnis existiert, wenn nicht, erstellen
if (!file_exists(__DIR__ . '/../database')) {
    mkdir(__DIR__ . '/../database');
}

// E-Mail-Konfiguration für Benachrichtigungen
define('EMAIL_FROM', 'statuspage@example.com');
define('EMAIL_NAME', 'Statuspage Benachrichtigungen');

// Anzahl der Tage, für die Störungen auf der Hauptseite angezeigt werden
define('INCIDENTS_DAYS', 14);

// Session-Timeout für den Admin-Bereich (in Sekunden)
define('SESSION_TIMEOUT', 3600); // 1 Stunde

// URL der Statuspage (ohne abschließenden Schrägstrich)
define('BASE_URL', 'http://localhost/statuspage');
?>