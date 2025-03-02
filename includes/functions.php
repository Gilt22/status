<?php
require_once('database.php');

/**
 * Gibt alle Hostgruppen zurück
 * @return array Liste aller Hostgruppen
 */
function getAllHostGroups() {
    $db = getDbConnection();
    $result = $db->query('SELECT * FROM host_groups ORDER BY name');
    
    $hostGroups = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hostGroups[] = $row;
    }
    
    $db->close();
    return $hostGroups;
}

/**
 * Gibt eine bestimmte Hostgruppe zurück
 * @param int $id ID der Hostgruppe
 * @return array|false Hostgruppe oder false wenn nicht gefunden
 */
function getHostGroup($id) {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM host_groups WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $hostGroup = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    return $hostGroup ?: false;
}

/**
 * Erstellt eine neue Hostgruppe
 * @param string $name Name der Hostgruppe
 * @param string $description Beschreibung der Hostgruppe
 * @return int|false ID der neu erstellten Hostgruppe oder false bei Fehler
 */
function createHostGroup($name, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare('INSERT INTO host_groups (name, description) VALUES (:name, :description)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $id = $db->lastInsertRowID();
        $db->close();
        return $id;
    }
    
    $db->close();
    return false;
}

/**
 * Aktualisiert eine Hostgruppe
 * @param int $id ID der Hostgruppe
 * @param string $name Name der Hostgruppe
 * @param string $description Beschreibung der Hostgruppe
 * @return bool True bei Erfolg, False bei Fehler
 */
function updateHostGroup($id, $name, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare('UPDATE host_groups SET name = :name, description = :description WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $db->close();
    
    return $result ? true : false;
}

/**
 * Löscht eine Hostgruppe
 * @param int $id ID der Hostgruppe
 * @return bool True bei Erfolg, False bei Fehler
 */
function deleteHostGroup($id) {
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM host_groups WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $db->close();
    
    return $result ? true : false;
}

/**
 * Gibt alle Hosts zurück, optional gefiltert nach Hostgruppe
 * @param int|null $groupId Optional: ID der Hostgruppe
 * @return array Liste der Hosts
 */
function getHosts($groupId = null) {
    $db = getDbConnection();
    
    if ($groupId) {
        $stmt = $db->prepare('SELECT * FROM hosts WHERE group_id = :group_id ORDER BY name');
        $stmt->bindValue(':group_id', $groupId, SQLITE3_INTEGER);
        $result = $stmt->execute();
    } else {
        $result = $db->query('SELECT h.*, g.name as group_name FROM hosts h 
                             LEFT JOIN host_groups g ON h.group_id = g.id 
                             ORDER BY h.name');
    }
    
    $hosts = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hosts[] = $row;
    }
    
    $db->close();
    return $hosts;
}

/**
 * Gibt einen bestimmten Host zurück
 * @param int $id ID des Hosts
 * @return array|false Host oder false wenn nicht gefunden
 */
function getHost($id) {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT h.*, g.name as group_name FROM hosts h 
                         LEFT JOIN host_groups g ON h.group_id = g.id 
                         WHERE h.id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $host = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    return $host ?: false;
}

/**
 * Erstellt einen neuen Host
 * @param string $name Name des Hosts
 * @param int|null $groupId ID der Hostgruppe oder null
 * @param string $description Beschreibung des Hosts
 * @return int|false ID des neu erstellten Hosts oder false bei Fehler
 */
function createHost($name, $groupId = null, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare('INSERT INTO hosts (name, group_id, description) VALUES (:name, :group_id, :description)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':group_id', $groupId, $groupId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $id = $db->lastInsertRowID();
        $db->close();
        return $id;
    }
    
    $db->close();
    return false;
}

/**
 * Aktualisiert einen Host
 * @param int $id ID des Hosts
 * @param string $name Name des Hosts
 * @param int|null $groupId ID der Hostgruppe oder null
 * @param string $description Beschreibung des Hosts
 * @return bool True bei Erfolg, False bei Fehler
 */
function updateHost($id, $name, $groupId = null, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare('UPDATE hosts SET name = :name, group_id = :group_id, description = :description WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':group_id', $groupId, $groupId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $db->close();
    
    return $result ? true : false;
}

/**
 * Löscht einen Host
 * @param int $id ID des Hosts
 * @return bool True bei Erfolg, False bei Fehler
 */
function deleteHost($id) {
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM hosts WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $db->close();
    
    return $result ? true : false;
}

/**
 * Erstellt einen neuen Vorfall
 * @param string $title Titel des Vorfalls
 * @param string $description Beschreibung des Vorfalls
 * @param string $status Status des Vorfalls
 * @param string $type Typ des Vorfalls (incident, performance, maintenance, etc.)
 * @param array $hostGroups IDs der betroffenen Hostgruppen
 * @param array $hosts IDs der betroffenen Hosts
 * @param string|null $scheduledStart Geplanter Startzeitpunkt (Format: Y-m-d H:i:s)
 * @param string|null $scheduledEnd Geplanter Endzeitpunkt (Format: Y-m-d H:i:s)
 * @return int|false ID des neu erstellten Vorfalls oder false bei Fehler
 */
function createIncident($title, $description, $status, $type = 'incident', $hostGroups = [], $hosts = [], $scheduledStart = null, $scheduledEnd = null) {
    $db = getDbConnection();
    
    // Transaktion starten
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Vorfall anlegen
        $stmt = $db->prepare('INSERT INTO incidents (title, description, status, type, scheduled_start, scheduled_end) 
                             VALUES (:title, :description, :status, :type, :scheduled_start, :scheduled_end)');
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':scheduled_start', $scheduledStart, $scheduledStart ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':scheduled_end', $scheduledEnd, $scheduledEnd ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->execute();
        
        $incidentId = $db->lastInsertRowID();
        
        // Erstes Update zur Störung anlegen
        $updateStmt = $db->prepare('INSERT INTO incident_updates (incident_id, message, status) VALUES (:incident_id, :message, :status)');
        $updateStmt->bindValue(':incident_id', $incidentId, SQLITE3_INTEGER);
        $updateStmt->bindValue(':message', $description, SQLITE3_TEXT);
        $updateStmt->bindValue(':status', $status, SQLITE3_TEXT);
        $updateStmt->execute();
        
        // Hostgruppen verknüpfen
        if (!empty($hostGroups)) {
            $groupStmt = $db->prepare('INSERT INTO incident_host_groups (incident_id, group_id) VALUES (:incident_id, :group_id)');
            
            foreach ($hostGroups as $groupId) {
                $groupStmt->bindValue(':incident_id', $incidentId, SQLITE3_INTEGER);
                $groupStmt->bindValue(':group_id', $groupId, SQLITE3_INTEGER);
                $groupStmt->execute();
                $groupStmt->reset();
            }
        }
        
        // Hosts verknüpfen
        if (!empty($hosts)) {
            $hostStmt = $db->prepare('INSERT INTO incident_hosts (incident_id, host_id) VALUES (:incident_id, :host_id)');
            
            foreach ($hosts as $hostId) {
                $hostStmt->bindValue(':incident_id', $incidentId, SQLITE3_INTEGER);
                $hostStmt->bindValue(':host_id', $hostId, SQLITE3_INTEGER);
                $hostStmt->execute();
                $hostStmt->reset();
            }
        }
        
        // Transaktion abschließen
        $db->exec('COMMIT');
        $db->close();
        
        // E-Mail-Benachrichtigungen versenden
        sendIncidentNotifications($incidentId);
        
        return $incidentId;
    } catch (Exception $e) {
        // Bei Fehler Transaktion zurückrollen
        $db->exec('ROLLBACK');
        $db->close();
        return false;
    }
}

/**
 * Aktualisiert den Status einer Störung und fügt ein Update hinzu
 * @param int $incidentId ID der Störung
 * @param string $message Update-Nachricht
 * @param string $status Neuer Status
 * @return bool True bei Erfolg, False bei Fehler
 */
function updateIncidentStatus($incidentId, $message, $status) {
    $db = getDbConnection();
    
    // Transaktion starten
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Status der Störung aktualisieren
        $stmt = $db->prepare('UPDATE incidents SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->bindValue(':id', $incidentId, SQLITE3_INTEGER);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->execute();
        
        // Wenn der Status "resolved" oder "completed" ist, das Lösungsdatum setzen
        if ($status == 'resolved' OR $status == 'completed') {
            $resolvedStmt = $db->prepare('UPDATE incidents SET resolved_at = CURRENT_TIMESTAMP WHERE id = :id');
            $resolvedStmt->bindValue(':id', $incidentId, SQLITE3_INTEGER);
            $resolvedStmt->execute();
        }
        
        // Update zur Störung hinzufügen
        $updateStmt = $db->prepare('INSERT INTO incident_updates (incident_id, message, status) VALUES (:incident_id, :message, :status)');
        $updateStmt->bindValue(':incident_id', $incidentId, SQLITE3_INTEGER);
        $updateStmt->bindValue(':message', $message, SQLITE3_TEXT);
        $updateStmt->bindValue(':status', $status, SQLITE3_TEXT);
        $updateStmt->execute();
        
        // Transaktion abschließen
        $db->exec('COMMIT');
        $db->close();
        
        // E-Mail-Benachrichtigungen versenden
        sendIncidentUpdateNotifications($incidentId);
        
        return true;
    } catch (Exception $e) {
        // Bei Fehler Transaktion zurückrollen
        $db->exec('ROLLBACK');
        $db->close();
        return false;
    }
}

/**
 * Gibt alle Vorfälle zurück, optional gefiltert nach Status und Zeitraum
 * @param string|null $status Optional: Status der Vorfälle
 * @param int|null $days Optional: Anzahl der Tage in der Vergangenheit
 * @param bool $includePlanned Optional: Ob geplante Wartungen einbezogen werden sollen
 * @return array Liste der Vorfälle
 */
function getIncidents($status = null, $days = null) {
    $db = getDbConnection();
    
    $query = 'SELECT * FROM incidents';
    $conditions = [];
    $params = [];
    
    if ($status) {
        $conditions[] = 'status = :status';
        $params[':status'] = $status;
    }
    
    if ($days) {
        $conditions[] = 'created_at >= datetime("now", "-' . $days . ' days")';
    }
    
    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    
    $query .= ' ORDER BY created_at DESC';
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value, SQLITE3_TEXT);
    }
    
    $result = $stmt->execute();
    
    $incidents = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $incidents[] = $row;
    }
    
    $db->close();
    return $incidents;
}

/**
 * Gibt Wartungsarbeiten für die Vergangenheit und Zukunft zurück
 * @param int $pastDays Anzahl der Tage in der Vergangenheit
 * @param int $futureDays Anzahl der Tage in der Zukunft
 * @return array Liste der Wartungsarbeiten
 */
function getMaintenanceByDateRange($pastDays = 7, $futureDays = 30) {
    $db = getDbConnection();
    
    // Wartungsarbeiten der letzten X Tage und zukünftige
    $query = "SELECT * FROM incidents 
              WHERE type = 'maintenance' 
              AND (
                  -- Vergangene Wartungen der letzten X Tage
                  (scheduled_start >= datetime('now', '-' || :past_days || ' days') 
                   AND scheduled_start <= datetime('now'))
                  OR
                  -- Zukünftige Wartungen
                  (scheduled_start > datetime('now') 
                   AND scheduled_start <= datetime('now', '+' || :future_days || ' days'))
              )
              ORDER BY scheduled_start ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':past_days', $pastDays, SQLITE3_INTEGER);
    $stmt->bindValue(':future_days', $futureDays, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    $maintenance = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $maintenance[] = $row;
    }
    
    $db->close();
    return $maintenance;
}

/**
 * Gibt Vorfälle basierend auf mehreren Status-Werten zurück
 * @param array $statuses Array mit Status-Werten
 * @param int|null $days Optional: Anzahl der Tage in der Vergangenheit
 * @return array Liste der Vorfälle
 */
function getIncidentsByStatus($statuses = [], $days = null) {
    if (empty($statuses)) {
        return getIncidents(null, $days);
    }
    
    $db = getDbConnection();
    
    $query = 'SELECT * FROM incidents WHERE status IN (';
    $placeholders = [];
    $params = [];
    
    foreach ($statuses as $index => $status) {
        $placeholder = ':status' . $index;
        $placeholders[] = $placeholder;
        $params[$placeholder] = $status;
    }
    
    $query .= implode(',', $placeholders) . ')';
    
    if ($days) {
        $query .= ' AND created_at >= datetime("now", "-' . $days . ' days")';
    }
    
    $query .= ' ORDER BY created_at DESC';
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value, SQLITE3_TEXT);
    }
    
    $result = $stmt->execute();
    
    $incidents = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $incidents[] = $row;
    }
    
    $db->close();
    return $incidents;
}

/**
 * Gibt eine bestimmte Störung mit allen Updates zurück
 * @param int $id ID der Störung
 * @return array|false Störung mit Updates oder false wenn nicht gefunden
 */
function getIncident($id) {
    $db = getDbConnection();
    
    // Störungsinformationen abrufen
    $stmt = $db->prepare('SELECT * FROM incidents WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $incident = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$incident) {
        $db->close();
        return false;
    }
    
    // Updates zur Störung abrufen
    $updatesStmt = $db->prepare('SELECT * FROM incident_updates WHERE incident_id = :id ORDER BY created_at ASC');
    $updatesStmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $updatesResult = $updatesStmt->execute();
    
    $incident['updates'] = [];
    while ($update = $updatesResult->fetchArray(SQLITE3_ASSOC)) {
        $incident['updates'][] = $update;
    }
    
    // Betroffene Hostgruppen abrufen
    $groupsStmt = $db->prepare('
        SELECT g.* FROM host_groups g
        JOIN incident_host_groups ihg ON g.id = ihg.group_id
        WHERE ihg.incident_id = :id
    ');
    $groupsStmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $groupsResult = $groupsStmt->execute();
    
    $incident['affected_groups'] = [];
    while ($group = $groupsResult->fetchArray(SQLITE3_ASSOC)) {
        $incident['affected_groups'][] = $group;
    }
    
    // Betroffene Hosts abrufen
    $hostsStmt = $db->prepare('
        SELECT h.* FROM hosts h
        JOIN incident_hosts ih ON h.id = ih.host_id
        WHERE ih.incident_id = :id
    ');
    $hostsStmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $hostsResult = $hostsStmt->execute();
    
    $incident['affected_hosts'] = [];
    while ($host = $hostsResult->fetchArray(SQLITE3_ASSOC)) {
        $incident['affected_hosts'][] = $host;
    }
    
    $db->close();
    return $incident;
}

/**
 * Fügt einen E-Mail-Abonnenten hinzu
 * @param string $email E-Mail-Adresse
 * @return string|false Token für die Bestätigung oder false bei Fehler
 */
function addSubscriber($email) {
    $db = getDbConnection();
    
    // Überprüfen, ob die E-Mail bereits existiert
    $checkStmt = $db->prepare('SELECT id FROM subscribers WHERE email = :email');
    $checkStmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $checkStmt->execute();
    
    if ($result->fetchArray(SQLITE3_ASSOC)) {
        $db->close();
        return false; // E-Mail existiert bereits
    }
    
    // Zufälliges Token generieren
    $token = bin2hex(random_bytes(16));
    
    // Abonnent hinzufügen
    $stmt = $db->prepare('INSERT INTO subscribers (email, token) VALUES (:email, :token)');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $db->close();
        
        // Bestätigungs-E-Mail senden
        sendConfirmationEmail($email, $token);
        
        return $token;
    }
    
    $db->close();
    return false;
}

/**
 * Bestätigt einen E-Mail-Abonnenten
 * @param string $token Bestätigungstoken
 * @return bool True bei Erfolg, False bei Fehler
 */
function confirmSubscriber($token) {
    $db = getDbConnection();
    $stmt = $db->prepare('UPDATE subscribers SET confirmed = 1 WHERE token = :token');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $changes = $db->changes();
    $db->close();
    
    return $changes > 0;
}

/**
 * Entfernt einen E-Mail-Abonnenten
 * @param string $token Bestätigungstoken
 * @return bool True bei Erfolg, False bei Fehler
 */
function unsubscribe($token) {
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM subscribers WHERE token = :token');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $changes = $db->changes();
    $db->close();
    
    return $changes > 0;
}

/**
 * Überprüft Admin-Anmeldedaten
 * @param string $username Benutzername
 * @param string $password Passwort
 * @return array|false Admin-Daten bei erfolgreicher Anmeldung, false bei Fehler
 */
function verifyAdminLogin($username, $password) {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM admins WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $admin = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Login-Zeit aktualisieren
        $updateStmt = $db->prepare('UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->bindValue(':id', $admin['id'], SQLITE3_INTEGER);
        $updateStmt->execute();
        
        $db->close();
        return $admin;
    }
    
    $db->close();
    return false;
}

/**
 * Gibt alle E-Mail-Templates zurück
 * @return array Liste aller E-Mail-Templates
 */
function getAllEmailTemplates() {
    $db = getDbConnection();
    $result = $db->query('SELECT * FROM email_templates ORDER BY name');
    
    $templates = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $templates[] = $row;
    }
    
    $db->close();
    return $templates;
}

/**
 * Gibt ein bestimmtes E-Mail-Template zurück
 * @param int $id ID des Templates
 * @return array|false Template oder false wenn nicht gefunden
 */
function getEmailTemplate($id) {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM email_templates WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $template = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    return $template ?: false;
}

/**
 * Gibt ein E-Mail-Template anhand des Namens zurück
 * @param string $name Name des Templates
 * @return array|false Template oder false wenn nicht gefunden
 */
function getEmailTemplateByName($name) {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM email_templates WHERE name = :name');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $template = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    return $template ?: false;
}

/**
 * Aktualisiert ein E-Mail-Template
 * @param int $id ID des Templates
 * @param string $name Name des Templates
 * @param string $subject Betreff des Templates
 * @param string $body Body des Templates
 * @param string $description Beschreibung des Templates
 * @return bool True bei Erfolg, false bei Fehler
 */
function updateEmailTemplate($id, $name, $subject, $body, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare('UPDATE email_templates SET name = :name, subject = :subject, body = :body, 
                          description = :description, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
    $stmt->bindValue(':body', $body, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    $db->close();
    
    return $result ? true : false;
}

/**
 * Erstellt ein neues E-Mail-Template
 * @param string $name Name des Templates
 * @param string $subject Betreff des Templates
 * @param string $body Body des Templates
 * @param string $description Beschreibung des Templates
 * @return int|false ID des neu erstellten Templates oder false bei Fehler
 */
function createEmailTemplate($name, $subject, $body, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare('INSERT INTO email_templates (name, subject, body, description) 
                         VALUES (:name, :subject, :body, :description)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
    $stmt->bindValue(':body', $body, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $id = $db->lastInsertRowID();
        $db->close();
        return $id;
    }
    
    $db->close();
    return false;
}

/**
 * Löscht ein E-Mail-Template
 * @param int $id ID des Templates
 * @return bool True bei Erfolg, false bei Fehler
 */
function deleteEmailTemplate($id) {
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM email_templates WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $db->close();
    
    return $result ? true : false;
}

/**
 * Gibt die E-Mail-Einstellungen zurück
 * @return array E-Mail-Einstellungen
 */
function getEmailSettings() {
    $db = getDbConnection();
    $result = $db->query('SELECT * FROM email_settings WHERE id = 1');
    
    $settings = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    return $settings ?: [
        'from_email' => 'statuspage@example.com',
        'from_name' => 'Statuspage Benachrichtigungen',
        'use_smtp' => 0
    ];
}

/**
 * Aktualisiert die E-Mail-Einstellungen
 * @param array $settings Die zu aktualisierenden Einstellungen
 * @return bool True bei Erfolg, false bei Fehler
 */
function updateEmailSettings($settings) {
    $db = getDbConnection();
    
    $stmt = $db->prepare('UPDATE email_settings SET 
                         from_email = :from_email, 
                         from_name = :from_name,
                         smtp_host = :smtp_host,
                         smtp_port = :smtp_port,
                         smtp_username = :smtp_username,
                         smtp_password = :smtp_password,
                         smtp_encryption = :smtp_encryption,
                         use_smtp = :use_smtp,
                         updated_at = CURRENT_TIMESTAMP 
                         WHERE id = 1');
                         
    $stmt->bindValue(':from_email', $settings['from_email'], SQLITE3_TEXT);
    $stmt->bindValue(':from_name', $settings['from_name'], SQLITE3_TEXT);
    $stmt->bindValue(':smtp_host', $settings['smtp_host'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':smtp_port', $settings['smtp_port'] ?? null, SQLITE3_INTEGER);
    $stmt->bindValue(':smtp_username', $settings['smtp_username'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':smtp_password', $settings['smtp_password'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':smtp_encryption', $settings['smtp_encryption'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':use_smtp', $settings['use_smtp'] ? 1 : 0, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $changes = $db->changes();
    
    // Wenn keine Aktualisierung stattfand, Datensatz einfügen
    if ($changes == 0) {
        $stmt = $db->prepare('INSERT INTO email_settings (id, from_email, from_name, smtp_host, smtp_port, 
                             smtp_username, smtp_password, smtp_encryption, use_smtp) 
                             VALUES (1, :from_email, :from_name, :smtp_host, :smtp_port, 
                             :smtp_username, :smtp_password, :smtp_encryption, :use_smtp)');
                             
        $stmt->bindValue(':from_email', $settings['from_email'], SQLITE3_TEXT);
        $stmt->bindValue(':from_name', $settings['from_name'], SQLITE3_TEXT);
        $stmt->bindValue(':smtp_host', $settings['smtp_host'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':smtp_port', $settings['smtp_port'] ?? null, SQLITE3_INTEGER);
        $stmt->bindValue(':smtp_username', $settings['smtp_username'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':smtp_password', $settings['smtp_password'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':smtp_encryption', $settings['smtp_encryption'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':use_smtp', $settings['use_smtp'] ? 1 : 0, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
    }
    
    $db->close();
    return $result ? true : false;
}

/**
 * Gibt alle bestätigten Abonnenten zurück
 * @return array Liste aller bestätigten Abonnenten
 */
function getConfirmedSubscribers() {
    $db = getDbConnection();
    $result = $db->query('SELECT * FROM subscribers WHERE confirmed = 1');
    
    $subscribers = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $subscribers[] = $row;
    }
    
    $db->close();
    return $subscribers;
}

/**
 * Ersetzt Variablen in einem Template-Text
 * @param string $text Der Template-Text
 * @param array $variables Array mit Variablen (Schlüssel => Wert)
 * @return string Der Text mit ersetzten Variablen
 */
function replaceTemplateVariables($text, $variables) {
    foreach ($variables as $key => $value) {
        $text = str_replace('{{' . $key . '}}', $value, $text);
    }
    return $text;
}

// Mails und Site-Funktionen importieren
require_once(__DIR__ . '/mail_functions.php');
require_once(__DIR__ . '/site_functions.php');

/**
 * Sendet eine Bestätigungs-E-Mail an einen neuen Abonnenten
 * @param string $email E-Mail-Adresse des Abonnenten
 * @param string $token Bestätigungstoken
 * @return bool True bei Erfolg, false bei Fehler
 */
function sendConfirmationEmail($email, $token) {
    $template = getEmailTemplateByName('subscription_confirmation');
    
    if (!$template) {
        return false;
    }
    
    $confirmationLink = BASE_URL . '/confirm.php?token=' . $token;
    
    $variables = [
        'confirmation_link' => $confirmationLink,
        'email' => $email
    ];
    
    $subject = replaceTemplateVariables($template['subject'], $variables);
    $body = replaceTemplateVariables($template['body'], $variables);
    
    // HTML-formatierten E-Mail-Body erstellen
    $htmlBody = formatEmailHtml($body, $subject);
    
    return sendEmail($email, $subject, $htmlBody, true);
}

/**
 * Sendet E-Mail-Benachrichtigungen bei neuen Störungen
 * @param int $incidentId ID der Störung
 */
function sendIncidentNotifications($incidentId) {
    $incident = getIncident($incidentId);
    
    if (!$incident) {
        return;
    }
    
    $template = getEmailTemplateByName('new_incident');
    
    if (!$template) {
        return;
    }
    
    $subscribers = getConfirmedSubscribers();
    
    if (empty($subscribers)) {
        return;
    }
    
    // Statustexte
    $statusTexts = [
        'planned' => 'Geplant',
        'investigating' => 'Untersuchung',
        'identified' => 'Identifiziert',
        'monitoring' => 'Überwachung',
        'resolved' => 'Behoben'
    ];
    
    // Betroffene Systeme auflisten
    $affectedSystems = [];
    
    foreach ($incident['affected_groups'] as $group) {
        $affectedSystems[] = 'Gruppe: ' . $group['name'];
    }
    
    foreach ($incident['affected_hosts'] as $host) {
        $affectedSystems[] = 'Host: ' . $host['name'];
    }
    
    $affectedSystemsText = !empty($affectedSystems) ? implode("\n", $affectedSystems) : 'Keine Angabe';
    
    // Typ der Störung bestimmen
    $typeLabels = [
        'incident' => 'Störung',
        'maintenance' => 'Wartung',
        'performance' => 'Leistungsproblem',
        'security' => 'Sicherheitsvorfall'
    ];
    
    // Zeitinformation basierend auf dem Typ
    $timeInfo = '';
    if ($incident['type'] === 'maintenance' && !empty($incident['scheduled_start'])) {
        $timeInfo = 'Zeitraum: ' . date('d.m.Y H:i', strtotime($incident['scheduled_start']));
        if (!empty($incident['scheduled_end'])) {
            $timeInfo .= ' bis ' . date('d.m.Y H:i', strtotime($incident['scheduled_end']));
        }
    } else {
        $timeInfo = 'Zeitpunkt: ' . date('d.m.Y H:i', strtotime($incident['created_at']));
    }
    
    // Variablen für das Template
    $variables = [
        'incident_title' => $incident['title'],
        'incident_type' => $typeLabels[$incident['type']] ?? 'Störung',
        'incident_status' => $statusTexts[$incident['status']] ?? $incident['status'],
        'time_info' => $timeInfo,
        'incident_description' => $incident['description'],
        'affected_systems' => $affectedSystemsText,
        'incident_url' => BASE_URL . '/incident.php?id=' . $incident['id']
    ];
    
    $subject = replaceTemplateVariables($template['subject'], $variables);
    $body = replaceTemplateVariables($template['body'], $variables);
    
    // HTML-formatierten E-Mail-Body erstellen
    $htmlBody = formatEmailHtml($body, $subject);
    
    foreach ($subscribers as $subscriber) {
        sendEmail($subscriber['email'], $subject, $htmlBody, true);
    }
}

/**
 * Sendet E-Mail-Benachrichtigungen bei Updates zu Störungen
 * @param int $incidentId ID der Störung
 */
function sendIncidentUpdateNotifications($incidentId) {
    $incident = getIncident($incidentId);
    
    if (!$incident || empty($incident['updates'])) {
        return;
    }
    
    $template = getEmailTemplateByName('incident_update');
    
    if (!$template) {
        return;
    }
    
    $subscribers = getConfirmedSubscribers();
    
    if (empty($subscribers)) {
        return;
    }
    
    // Letztes Update
    $latestUpdate = end($incident['updates']);
    
    // Statustexte
    $statusTexts = [
        'planned' => 'Geplant',
        'investigating' => 'Untersuchung',
        'identified' => 'Identifiziert',
        'monitoring' => 'Überwachung',
        'resolved' => 'Behoben'
    ];
    
    // Variablen für das Template
    $variables = [
        'incident_title' => $incident['title'],
        'incident_status' => $statusTexts[$latestUpdate['status']] ?? $latestUpdate['status'],
        'update_time' => date('d.m.Y H:i', strtotime($latestUpdate['created_at'])),
        'update_message' => $latestUpdate['message'],
        'incident_url' => BASE_URL . '/incident.php?id=' . $incident['id']
    ];
    
    $subject = replaceTemplateVariables($template['subject'], $variables);
    $body = replaceTemplateVariables($template['body'], $variables);
    
    // HTML-formatierten E-Mail-Body erstellen
    $htmlBody = formatEmailHtml($body, $subject);
    
    foreach ($subscribers as $subscriber) {
        sendEmail($subscriber['email'], $subject, $htmlBody, true);
    }
}

/**
 * Überprüft, ob ein Benutzer eingeloggt ist
 * @return bool True wenn eingeloggt, false wenn nicht
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Prüfen, ob die Session abgelaufen ist
    if ($_SESSION['last_activity'] < time() - SESSION_TIMEOUT) {
        // Session ist abgelaufen, Benutzer ausloggen
        session_unset();
        session_destroy();
        return false;
    }
    
    // Letzte Aktivität aktualisieren
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Hilfsfunktion zur HTML-Escape
 * @param string $str Zu escapen String
 * @return string Escapeter String
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>