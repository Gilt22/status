<?php
require_once('includes/functions.php');
require_once('includes/site_functions.php');

$siteSettings = getSiteSettings();
$incidentId = isset($_GET['id']) ? $_GET['id'] : '';
$incident = getIncident($incidentId);

if (!$incident) {
    header('Location: index.php');
    exit;
}

$hostGroups = getAllHostGroups();
$hosts = getHosts();
?>
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($incident['title']); ?> - <?php echo h($siteSettings['site_title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <header class="text-center mb-12">
            <?php if (!empty($siteSettings['logo_path'])): ?>
                <div class="mb-6">
                    <img src="<?php echo h($siteSettings['logo_path']); ?>" 
                         alt="<?php echo h($siteSettings['company_name']); ?>" 
                         class="h-12 mx-auto">
                </div>
            <?php endif; ?>
            <h1 class="text-4xl font-bold text-gray-900 mb-2"><?php echo h($siteSettings['site_title']); ?></h1>
        </header>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <h2 class="text-2xl font-semibold text-gray-900"><?php echo h($incident['title']); ?></h2>
                    <span class="px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap
                        <?php
                        switch($incident['status']) {
                            case 'investigating':
                                echo 'bg-yellow-100 text-yellow-800';
                                break;
                            case 'identified':
                                echo 'bg-red-100 text-red-800';
                                break;
                            case 'monitoring':
                                echo 'bg-blue-100 text-blue-800';
                                break;
                            case 'resolved':
                                echo 'bg-gray-100 text-gray-800';
                                break;
                            default:
                                echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?php
                        $statusTexts = [
                            'investigating' => 'Wird untersucht',
                            'identified' => 'Problem erkannt',
                            'monitoring' => 'Wird überwacht',
                            'resolved' => 'Behoben'
                        ];
                        echo $statusTexts[$incident['status']] ?? $incident['status'];
                        ?>
                    </span>
                </div>

                <div class="space-y-1 text-sm text-gray-500 mb-6">
                    <p>Gemeldet: <?php echo date('d.m.Y H:i', strtotime($incident['created_at'])); ?></p>
                    <?php if ($incident['status'] === 'resolved' && $incident['resolved_at']): ?>
                        <p>Behoben: <?php echo date('d.m.Y H:i', strtotime($incident['resolved_at'])); ?></p>
                    <?php endif; ?>
                </div>

                <div class="prose max-w-none text-gray-600 mb-8">
                    <?php echo nl2br(h($incident['description'])); ?>
                </div>

                <?php if (!empty($incident['affected_groups'])): ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Betroffene Systeme</h3>
                        <div class="space-y-4">
                            <?php foreach ($incident['affected_groups'] as $group): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2"><?php echo h($group['name']); ?></h4>
                                    <?php
                                    $groupHosts = array_filter($incident['affected_hosts'], function($host) use ($group) {
                                        return $host['group_id'] == $group['id'];
                                    });
                                    if (!empty($groupHosts)):
                                    ?>
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <?php foreach ($groupHosts as $host): ?>
                                                <li><?php echo h($host['name']); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                $ungroupedHosts = array_filter($incident['affected_hosts'], function($host) {
                    return empty($host['group_id']);
                });
                if (!empty($ungroupedHosts)):
                ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Einzelne betroffene Systeme</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <ul class="space-y-1 text-sm text-gray-600">
                                <?php foreach ($ungroupedHosts as $host): ?>
                                    <li><?php echo h($host['name']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($incident['updates'])): ?>
                    <div class="relative">
                        <div class="absolute top-0 bottom-0 left-4 w-0.5 bg-gray-200"></div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 relative">Updates</h3>
                        <div class="space-y-6">
                            <?php foreach ($incident['updates'] as $update): ?>
                                <div class="relative pl-8">
                                    <div class="absolute left-0 top-2 w-8 flex items-center justify-center">
                                        <div class="h-4 w-4 rounded-full border-2 border-gray-200 bg-white"></div>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-center justify-between gap-4 mb-2">
                                            <span class="text-sm text-gray-500">
                                                <?php echo date('d.m.Y H:i', strtotime($update['created_at'])); ?>
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php
                                                switch($update['status']) {
                                                    case 'investigating':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'identified':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'monitoring':
                                                        echo 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'resolved':
                                                        echo 'bg-gray-100 text-gray-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo $statusTexts[$update['status']] ?? $update['status']; ?>
                                            </span>
                                        </div>
                                        <div class="text-gray-600">
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

        <div class="mt-8 text-center">
            <a href="index.php" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Zurück zur Übersicht
            </a>
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