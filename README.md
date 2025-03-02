# 🚦 Statuspage

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

Eine professionelle, PHP-basierte Statuspage zur Anzeige des Systemstatus und zur Verwaltung von Störungsmeldungen. Ideal für Unternehmen, die ihren Kunden transparente Informationen über den Zustand ihrer Dienste bieten möchten.

![Statuspage Preview](assets/img/preview.png)

## ✨ Hauptfunktionen

🔧 **Admin-Dashboard**
- Umfassender Admin-Bereich zum Verwalten von Hostgruppen und Hosts
- Intuitive Benutzeroberfläche für effizientes Management

🚨 **Incident Management**
- Professionelle Verwaltung von Störungsmeldungen
- Detaillierte Verlaufsansicht und Statusupdates

📧 **Benachrichtigungen**
- Automatische E-Mail-Benachrichtigungen bei Störungen
- Anpassbare E-Mail-Templates

🎨 **Anpassbares Design**
- Logo-Upload Funktion
- Flexibles Farbschema
- Responsive Design für alle Geräte

💾 **Einfache Wartung**
- SQLite-Datenbank ohne zusätzliche Server
- Automatische Backups
    

## 🚀 Installation

### Voraussetzungen

- Debian/Ubuntu Server mit Root-Zugriff
- Öffentlich erreichbare Domain(s)
- PHP >= 7.4
- Webserver (Apache2 oder Nginx)

### Schnellinstallation

1. **Installer-Skript herunterladen**
   ```bash
   nano install.sh
   ```

2. **Skript ausführbar machen**
   ```bash
   chmod +x install.sh
   ```

3. **Installation starten**
   ```bash
   ./install.sh
   ```

4. **Konfiguration durchführen**
   - Domains eingeben
   - SSL-Zertifikate einrichten (optional)
   - Web-Installer durchlaufen

Detaillierte Installationsanweisungen finden Sie in der [Installationsanleitung](docs/INSTALL.md).
    

## 🛠 Konfiguration & Nutzung

### Admin-Bereich

Der Admin-Bereich bietet folgende Funktionen:

- **Dashboard**: Übersicht über Hostgruppen, Hosts und aktuelle Störungen
- **Hostgruppen**: Verwaltung von logischen Gruppen für Ihre Dienste
- **Hosts**: Verwaltung einzelner Komponenten und deren Status
- **Störungen**: Erstellung und Verwaltung von Störungsmeldungen
- **E-Mail-Einstellungen**: Konfiguration der E-Mail-Parameter
- **Website-Einstellungen**: Anpassung von Logo, Farben und Firmeninformationen

### Öffentliche Seite

- 📊 Übersichtliche Statusanzeige aller Komponenten
- 📜 Detaillierte Störungshistorie
- 📧 E-Mail-Benachrichtigungen für Statusupdates

## 🔒 Sicherheit

- Regelmäßige Updates durchführen
- HTTPS-Verschlüsselung verwenden
- Starke Passwörter nutzen
- Backups der SQLite-Datenbank erstellen

## 🤝 Mitwirken

Beiträge sind willkommen! So können Sie helfen:

- 🐛 Fehler melden
- 💡 Neue Funktionen vorschlagen
- 📝 Dokumentation verbessern
- 🔧 Pull Requests einreichen

## 📄 Lizenz

Dieses Projekt steht unter der [MIT-Lizenz](LICENSE).