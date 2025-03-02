<?php
require_once('includes/functions.php');
require_once('includes/site_functions.php');

$siteSettings = getSiteSettings();
$message = '';
$error = '';

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!empty($token)) {
    if (unsubscribe($token)) {
        $message = 'Sie wurden erfolgreich von den Benachrichtigungen abgemeldet.';
    } else {
        $error = 'Der Abmeldelink ist ungültig oder wurde bereits verwendet.';
    }
} else {
    $error = 'Kein Abmeldetoken gefunden.';
}
?>
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abmeldung - <?php echo h($siteSettings['site_title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <?php if (!empty($siteSettings['logo_path'])): ?>
                <div class="mb-6">
                    <img src="<?php echo h($siteSettings['logo_path']); ?>" 
                         alt="<?php echo h($siteSettings['company_name']); ?>" 
                         class="h-12 mx-auto">
                </div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="rounded-lg bg-green-50 p-6 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?php echo h($message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="rounded-lg bg-red-50 p-6 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?php echo h($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6">
                <a href="index.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Zurück zur Startseite
                </a>
            </div>
        </div>

        <footer class="mt-12 text-center text-sm text-gray-500">
            <p>
                &copy; <?php echo date('Y'); ?> - <?php echo h($siteSettings['company_name'] ?: 'Statuspage'); ?>
                <?php if (!empty($siteSettings['imprint_url'])): ?>
                    | <a href="<?php echo h($siteSettings['imprint_url']); ?>" class="text-gray-600 hover:text-gray-900">Impressum</a>
                <?php endif; ?>
                <?php if (!empty($siteSettings['privacy_url'])): ?>
                    | <a href="<?php echo h($siteSettings['privacy_url']); ?>" class="text-gray-600 hover:text-gray-900">Datenschutz</a>
                <?php endif; ?>
            </p>
        </footer>
    </div>
</body>
</html>