<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier est un essai d'utilisation des Server Side Events
 * pour la mise en place d'une connexion bi-directionnelle entre les utilisateurs et le serveur.
 * Le travail a été mis en pause suite à des problèmes d'emballements à diagnostiquer.
 */

use EnedisLabBZH\Core\CoreMysqli;

die();

!headers_sent() && @header('Content-Type: text/event-stream');
!headers_sent() && @header('Cache-Control: no-cache');
!headers_sent() && @header('Connection: keep-alive');

require_once '../my-config.inc.php';
require_once 'core_include.php';
require_once 'core_functions.php';
require '../my-config.inc.php';

// Nginx: unbuffered responses suitable for Comet and HTTP streaming applications
!headers_sent() && @header('X-Accel-Buffering: no');

session_status() === PHP_SESSION_ACTIVE
    && session_write_close();

$lastTimeStamp = getIsoDateWithMilliseconds();
$isRuningOnDevServer = isRuningOnDevServer();
$isRuningOnDevServer && error_log("\$lastTimeStamp initialized at " . $lastTimeStamp);
$interval = 1;
$killAfter = 60;
$start = time();
$table = "`{$_tablesPrefix}core_events`";

$query =
    "CREATE TABLE IF NOT EXISTS {$table} (
`lastEventId` varchar(40) DEFAULT NULL,
`type` varchar(40) DEFAULT NULL,

`data` varchar( 21340 ) DEFAULT NULL,

`timeStamp` char(24) DEFAULT NULL,
KEY `timeStampIndex` (`timeStamp`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='Table de stockage des Server Side Events';
";

CoreMysqli::get()->query($query);

session_status() === PHP_SESSION_ACTIVE
    && session_write_close();

while (ob_get_level()) {
    ob_end_clean();
}
doFlush();

while ((time() - $start) < $killAfter) {
    sleep($interval);

    try {
        $query = "SELECT `lastEventId`,`type`,`data`,`timeStamp` FROM $table WHERE timeStamp > ? ORDER BY `timeStamp`";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('s', $lastTimeStamp);
        $stmt->execute();
        $lastEventId = $type = $data = $timeStamp = null;
        $stmt->bind_result($lastEventId, $type, $json, $timeStamp);
        while ($stmt->fetch()) { //} && $lastEventId !== $_sessionId) {
            $lastTimeStamp = $timeStamp;
            $data = [['lastEventId' => $lastEventId, 'type' => $type, 'data' => $json]];
            echo 'id:' . $lastEventId . "\n";
            echo 'event:' . $type . "\n";
            echo 'data:' . json_encode($data) . "\n\n";
            doFlush();
            doFlush();
            doFlush();
            doFlush();
            doFlush();
        }
        close($stmt);
    } catch (Exception $e) {
        $isRuningOnDevServer && error_log("\$e->getMessage() = " . $e->getMessage());
        $isRuningOnDevServer && error_log("\$e->getTraceAsString() = " . $e->getTraceAsString());
        close($stmt);
        die();
    }

    // if the connection has been closed by the client we better exit the loop
    if (connection_aborted()) {
        close($stmt);
        die();
    }
}
close($stmt);

function doFlush()
{
    @ob_flush();
    @flush();
}

function close($stmt)
{
    if (is_resource($stmt) && get_resource_type($stmt) === 'mysql link') {
        $stmt->close();
    }
}
