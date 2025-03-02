<?php
$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$success = false;

if ($token) {
    if (confirmSubscription($token)) {
        $message = 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie erhalten ab jetzt Benachrichtigungen über neue Störungen.';
        $success = true;
    } else {
        $message = 'Der Bestätigungslink ist ungültig oder wurde bereits verwendet.';
    }
} else {
    $message = 'Kein Bestätigungstoken gefunden.';
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4 text-center">
                <?php if ($success): ?>
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" 
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-check-lg text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h2 class="h4 mb-3">E-Mail bestätigt</h2>
                <?php else: ?>
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" 
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-x-lg text-danger" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h2 class="h4 mb-3">Fehler bei der Bestätigung</h2>
                <?php endif; ?>
                
                <p class="text-muted mb-4"><?php echo h($message); ?></p>
                
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Zurück zur Startseite
                </a>
            </div>
        </div>
    </div>
</div>