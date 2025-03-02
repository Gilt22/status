<?php
session_start();
require_once('../includes/functions.php');

// Wenn bereits eingeloggt, zur Admin-Hauptseite weiterleiten
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Login-Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Bitte geben Sie Benutzername und Passwort ein.';
    } else {
        $admin = verifyAdminLogin($username, $password);
        
        if ($admin) {
            // Login erfolgreich
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['last_activity'] = time();
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Ungültiger Benutzername oder Passwort.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Statuspage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1>Admin Login</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Benutzername</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Passwort</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Anmelden</button>
                </div>
            </form>
            
            <p><a href="../index.php">Zurück zur Statuspage</a></p>
        </div>
    </div>
</body>
</html>