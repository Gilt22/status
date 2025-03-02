# Statuspage

Eine PHP-basierte Statuspage zur Anzeige des Systemstatus und zur Verwaltung von Störungsmeldungen.

## Funktionen

- Admin-Bereich zum Verwalten von Hostgruppen und Hosts
- Erstellung und Verwaltung von Störungsmeldungen
- E-Mail-Benachrichtigungen bei neuen Störungen und Updates
- Anpassbare Designs mit Logo-Upload und Farbschemata
- Responsive Design für Desktop und Mobile

## Installation

### Voraussetzungen

- PHP 7.2 oder höher
- SQLite-Unterstützung für PHP
- Composer (für PHPMailer-Installation)

### Installationsschritte

1. Laden Sie alle Dateien auf Ihren Webserver hoch
2. Stellen Sie sicher, dass die Verzeichnisse `database` und `assets/img` Schreibrechte haben
3. Installieren Sie PHPMailer über Composer:

```bash
cd statuspage
composer require phpmailer/phpmailer
```

4. Rufen Sie die Seite im Browser auf - die Datenbank wird automatisch erstellt
5. Melden Sie sich im Admin-Bereich an mit:
   - Benutzername: `admin`
   - Passwort: `admin123`
6. Ändern Sie sofort das Standard-Passwort

### Konfiguration

Nach der Installation sollten Sie folgende Einstellungen vornehmen:

1. **E-Mail-Einstellungen**: Konfigurieren Sie die E-Mail-Parameter unter "E-Mail-Einstellungen"
2. **Website-Einstellungen**: Passen Sie Logo, Farben und Firmeninformationen unter "Website-Einstellungen" an
3. **E-Mail-Templates**: Passen Sie bei Bedarf die E-Mail-Vorlagen unter "E-Mail-Templates" an

## Verwendung

### Admin-Bereich

- **Dashboard**: Übersicht über Hostgruppen, Hosts und aktuelle Störungen
- **Hostgruppen**: Verwaltung von Hostgruppen
- **Hosts**: Verwaltung von Hosts
- **Störungen**: Erstellung und Verwaltung von Störungsmeldungen
- **E-Mail-Einstellungen**: Konfiguration der E-Mail-Parameter
- **E-Mail-Templates**: Anpassung der E-Mail-Vorlagen
- **Website-Einstellungen**: Anpassung von Logo, Farben und Firmeninformationen

### Öffentliche Seite

- Anzeige des aktuellen Systemstatus
- Detailansicht für Störungen
- Anmeldung für E-Mail-Benachrichtigungen

## Sicherheitshinweise

- Ändern Sie sofort das Standard-Admin-Passwort
- Verwenden Sie HTTPS für die Statuspage
- Beschränken Sie den Zugriff auf den Admin-Bereich bei Bedarf durch zusätzliche Maßnahmen (z.B. .htaccess)
- Erstellen Sie regelmäßige Backups der SQLite-Datenbank