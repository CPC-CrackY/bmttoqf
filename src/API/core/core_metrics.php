<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier est appelé toutes les minutes par une tâche cron.
 * Il permet de détecter les problèmes de ressources serveur avant la panne.
 */

require_once __DIR__ . '/../my-config.inc.php';
require_once __DIR__ . '/core_include.php';

$cpu_percent = getCpuUsage();
$memory_percent = getMemoryUsage();
$disk_percent = getDiskUsage();
$emailSent = false;

if (
    ($cpu_percent > 95 || $memory_percent > 95 || $disk_percent > 95)
    && !file_exists('./mail_sent.flag')
) {
    touch('./mail_sent.flag');
    sendTechnicalMail(
        'Serveur saturé',
        "Merci de pendre en compte l'erreur suivante :
        \$cpu_percent = $cpu_percent %
        \$memory_percent = $memory_percent %
        \$disk_percent = $disk_percent %

        Cordialement."
    );
    $emailSent = true;
}

saveMetrics($cpu_percent, $memory_percent, $disk_percent);
echo sprintf("[%s]\tcpu_percent: %.2f%% |\tmemory_percent: %.2f%% |\tdisk_percent: %.2f%%\t%s\n", date('Y-m-d H:i:s'), $cpu_percent, $memory_percent, $disk_percent, $emailSent ? "(Email alerte envoyé)" : "");
