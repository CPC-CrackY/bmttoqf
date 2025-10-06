<?php

require_once __DIR__ . '/common.inc.php';

use EnedisLabBZH\Core\CoreDbSynchronize\CoreDatabaseImporter;

/**
 * Récupération du fichier ZIP sur la PROD
 */
$prodServerUrl = getProdServerBaseUrl() . "/API/backup_db/{$todaysFile}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $prodServerUrl);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Augmentez le timeout pour les gros fichiers
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$pathToTodaysFile) {
    $fp = fopen($pathToTodaysFile, 'a');
    $length = fwrite($fp, $data);
    fclose($fp);

    return $length;
});
$result = curl_exec($ch);
if ($result === false) {
    die("Erreur cURL : " . curl_error($ch));
}
curl_close($ch);

/**
 * Lancement de l'import
 */
if (file_exists($pathToTodaysFile) && filesize($pathToTodaysFile) > 20 * 1024) {
    $importer = new CoreDatabaseImporter($pathToTodaysFile, $folder);
    $importer->import();
} else {
    echo "Le fichier est trop petit.";
}

/**
 * Nettoyage des fichiers temporaires
 */
foreach (glob($folder . '/*.sql') as $file) {
    unlink($file);
}
echo "<br>\nTemp SQL files on DEV are deleted.";
file_exists($pathToTodaysFile) && unlink($pathToTodaysFile);
echo "<br>\nTemp ZIP file on DEV is deleted.";

echo "<br>\nImport done!";
