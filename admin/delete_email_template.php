<?php
session_start();
require_once('../includes/functions.php');

// Sicherstellen, dass der Benutzer eingeloggt ist
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Sicherstellen, dass wir nicht alle Templates löschen
    $templates = getAllEmailTemplates();
    if (count($templates) > 4) { // Mindestens 4 Standard-Templates behalten
        deleteEmailTemplate($id);
    }
}

header('Location: email_templates.php?deleted=1');
exit;
?>