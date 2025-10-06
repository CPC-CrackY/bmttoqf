<?php

require_once __DIR__ . '/../../../autoload.php';
include_once __DIR__ . '/../../../../my-config.inc.php'; // for $_tablesPrefix
include_once __DIR__ . '/../../../core_include.php';

use EnedisLabBZH\Core\CoreDbSynchronize\Anonymizer;

flushAll();
echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<style>
body {
    background-color: black;
    padding: 10px;
    font-family: Consolas, "Lucida console", "Courier New", monospace;
    color: green;
    }
    </style>
    </head>
    <body>';

$folder = __DIR__ . '/../../../../backup_db';
@mkdir($folder);
$folder = realpath($folder);
$anonymizer = new Anonymizer();

$todaysFile = $anonymizer->shuffleIdentifier(date('dDmMY')) . '.zip';
$pathToTodaysFile = $folder . '/' . $todaysFile;

/**
 * Nettoyage des fichiers temporaires (.sql et .zip)
 */
foreach (glob($folder . '/*.sql') as $file) {
    unlink($file);
}
file_exists($pathToTodaysFile) && unlink($pathToTodaysFile);
