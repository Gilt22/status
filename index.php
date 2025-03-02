<?php
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

require_once('includes/functions.php');
require_once('includes/site_functions.php');

$siteSettings = getSiteSettings();

// Bestimme welche Seite geladen werden soll
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Validiere den Seitennamen für Sicherheit
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// Prüfe ob die Seite existiert
$pagePath = "pages/{$page}.php";
if (!file_exists($pagePath)) {
    $page = 'home';
    $pagePath = "pages/home.php";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($siteSettings['site_title']); ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .badge-operational {
            background-color: #28a745;
            color: white;
        }
        .badge-investigating {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-identified {
            background-color: #fd7e14;
            color: white;
        }
        .badge-progress {
            background-color: #17a2b8;
            color: white;
        }
        .badge-monitoring {
            background-color: #6f42c1;
            color: white;
        }
        .badge-planned {
            background-color: #007bff;
            color: white;
        }
        .badge-resolved {
            background-color: #28a745;
            color: white;
        }
        .badge-completed {
            background-color: #28a745;
            color: white;
        }
        /* Timeline-Bars */
        .timeline-bar {
            height: 8px;
        }
        .status-operational {
            background-color: #28a745;
        }
        .status-investigating {
            background-color: #ffc107;
        }
        .status-identified {
            background-color: #fd7e14;
        }
        .status-progress {
            background-color: #17a2b8;
        }
        .status-monitoring {
            background-color: #6f42c1;
        }
        .status-resolved {
            background-color: #28a745;
        }
        .status-completed {
            background-color: #28a745;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <header class="text-center mb-5">
            <?php if (!empty($siteSettings['logo_path'])): ?>
                <div class="mb-4">
                    <img src="<?php echo h($siteSettings['logo_path']); ?>" 
                         alt="<?php echo h($siteSettings['company_name']); ?>" 
                         class="img-fluid" style="max-height: 60px;">
                </div>
            <?php endif; ?>
            <h1 class="h2 fw-bold mb-0"><?php echo h($siteSettings['site_title']); ?></h1>
            <?php if ($page === 'home'): ?>
                <p class="lead text-muted mt-2">Aktuelle Informationen zum Status unserer Systeme</p>
            <?php endif; ?>
        </header>

        <!-- Hauptinhalt -->
        <?php include $pagePath; ?>

        <footer class="mt-5 text-center text-muted">
            <?php if (!empty($siteSettings['custom_footer_text'])): ?>
                <div class="mb-3">
                    <?php echo nl2br(h($siteSettings['custom_footer_text'])); ?>
                </div>
            <?php endif; ?>
            
            <p class="small">
                &copy; <?php echo date('Y'); ?> - <?php echo h($siteSettings['company_name'] ?: 'Statuspage'); ?>
                <?php if (!empty($siteSettings['imprint_url'])): ?>
                    | <a href="<?php echo h($siteSettings['imprint_url']); ?>" class="text-decoration-none text-muted">Impressum</a>
                <?php endif; ?>
                <?php if (!empty($siteSettings['privacy_url'])): ?>
                    | <a href="<?php echo h($siteSettings['privacy_url']); ?>" class="text-decoration-none text-muted">Datenschutz</a>
                <?php endif; ?>
                | <a href="admin/login.php" class="text-decoration-none text-muted">Admin Login</a>
            </p>
        </footer>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>