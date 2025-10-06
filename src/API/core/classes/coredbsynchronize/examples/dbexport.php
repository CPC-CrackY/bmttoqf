<?php

require_once __DIR__ . '/common.inc.php';

if (php_sapi_name() !== 'cli' && !isset($_GET['pass'])) {
    die('Do you mind if I sleep?');
}

use EnedisLabBZH\Core\CoreDbSynchronize\CorePartialDatabaseExporter;

$exporter = new CorePartialDatabaseExporter($pathToTodaysFile);
$exporter->create();

echo "<br>\nExport done!";

echo "<br>\nRequesting import to DEV server : ";

$devServerUrl = getDevServerBaseUrl() . '/API/core/classes/coredbsynchronize/examples/dbimport.php';
echo $devServerUrl;
echo "<br>\n<div style=\"color:blue; padding-left: 1cm;\">";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $devServerUrl);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 3600); // Des fois que l'import dure 1 heure...
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
    echo $data;

    return strlen($data);
});

$result = curl_exec($ch);
echo "<br>\n</div>";

if ($result === false) {
    die("Erreur cURL : " . curl_error($ch));
}
file_exists($pathToTodaysFile) && unlink($pathToTodaysFile);
echo "<br>\nTemp file on PROD is deleted.";
curl_close($ch);
