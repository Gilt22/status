<?php
// Hole Incident ID aus URL
$incidentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$incidentId) {
    header('Location: index.php');
    exit;
}

// Hole Incident Details
$incident = getIncident($incidentId);
if (!$incident) {
    header('Location: index.php');
    exit;
}

// Hole betroffene Gruppen und Hosts
$affectedGroups = [];
$affectedHosts = [];

if (isset($incident['affected_groups'])) {
    foreach ($incident['affected_groups'] as $group) {
        $groupDetails = getHostGroup($group['id']);
        if ($groupDetails) {
            $affectedGroups[] = $groupDetails;
        }
    }
}

if (isset($incident['affected_hosts'])) {
    foreach ($incident['affected_hosts'] as $host) {
        $hostDetails = getHost($host['id']);
        if ($hostDetails) {
            $affectedHosts[] = $hostDetails;
        }
    }
}

// Status-Text-Mapping
$statusTexts = [
    'operational' => 'Betriebsbereit',
    'planned' => 'Geplante Wartung',
    'investigating' => 'Wird untersucht',
    'identified' => 'Problem erkannt',
    'monitoring' => 'Wird überwacht',
    'resolved' => 'Behoben'
];

// Status-Badge-Klassen
$statusBadgeClasses = [
    'operational' => 'badge-operational',
    'planned' => 'badge-planned',
    'investigating' => 'badge-investigating',
    'identified' => 'badge-identified',
    'monitoring' => 'badge-monitoring',
    'resolved' => 'badge-resolved'
];
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <h2 class="h3 mb-0"><?php echo h($incident['title']); ?></h2>
                    <span class="badge rounded-pill <?php echo $statusBadgeClasses[$incident['status']] ?? ''; ?>">
                        <?php echo $statusTexts[$incident['status']] ?? $incident['status']; ?>
                    </span>
                </div>

                <div class="text-muted mb-4">
                    <?php if ($incident['status'] === 'planned'): ?>
                        <?php
                        $plannedStart = isset($incident['planned_start']) ? strtotime($incident['planned_start']) : null;
                        $plannedEnd = isset($incident['planned_end']) ? strtotime($incident['planned_end']) : null;
                        ?>
                        <?php if ($plannedStart): ?>
                            <p class="mb-2">
                                <i class="bi bi-calendar me-2"></i>
                                Geplant für: <?php echo date('d.m.Y H:i', $plannedStart); ?>
                                <?php if ($plannedEnd): ?>
                                    - <?php echo date('d.m.Y H:i', $plannedEnd); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <p>
                            <i class="bi bi-clock me-2"></i>
                            Erwartete Dauer: <?php echo h($incident['expected_duration'] ?? 'Nicht angegeben'); ?>
                        </p>
                    <?php else: ?>
                        <p class="mb-2">
                            <i class="bi bi-clock me-2"></i>
                            Gemeldet: <?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?>
                        </p>
                        <?php if ($incident['status'] === 'resolved' && $incident['resolved_at']): ?>
                            <p>
                                <i class="bi bi-check-circle me-2"></i>
                                Behoben: <?php echo date('d.m.Y H:i', strtotime($incident['resolved_at'])); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <h3 class="h6 fw-bold mb-3">Beschreibung</h3>
                    <div class="text-muted">
                        <?php echo nl2br(h($incident['description'])); ?>
                    </div>
                </div>

                <?php if (!empty($affectedGroups) || !empty($affectedHosts)): ?>
                    <div class="mb-4">
                        <h3 class="h6 fw-bold mb-3">Betroffene Systeme</h3>
                        <?php if (!empty($affectedGroups)): ?>
                            <div class="mb-3">
                                <h4 class="h6 text-muted mb-2">Gruppen:</h4>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($affectedGroups as $group): ?>
                                        <div class="list-group-item px-0">
                                            <?php echo h($group['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($affectedHosts)): ?>
                            <div>
                                <h4 class="h6 text-muted mb-2">Hosts:</h4>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($affectedHosts as $host): ?>
                                        <div class="list-group-item px-0">
                                            <?php echo h($host['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($incident['updates'])): ?>
                    <div>
                        <h3 class="h6 fw-bold mb-3">Updates</h3>
                        <div class="timeline">
                            <?php foreach ($incident['updates'] as $update): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge rounded-pill <?php echo $statusBadgeClasses[$update['status']] ?? ''; ?>">
                                                <?php echo $statusTexts[$update['status']] ?? $update['status']; ?>
                                            </span>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y H:i', strtotime($update['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-muted">
                                            <?php echo nl2br(h($update['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i>
                Zurück zur Übersicht
            </a>
        </div>
    </div>
</div>