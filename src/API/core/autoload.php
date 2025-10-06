<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier importe automatiquement des classes déclarées dans :
 *     pour le squelette :      API/core/classes/{name}/{name}.class.php
 *     pour votre application : API/classes/{app}/{name}/{name}.class.php
 * (dans votre application, pensez à utiliser le namespace \EnedisLabBZH\{app})
 */
$autoload_dir = __DIR__;
spl_autoload_register(function ($class) {
    global $autoload_dir;
    $extract = strtolower(str_replace('\\', '/', $class));
    $extract = str_replace(['enedislabbzh/core', 'enedislabbzh'], '', $extract);
    $classFolder = (substr_count($extract, '/') > 1) ?
        substr($extract, 0, strrpos($extract, '/')) :
        $extract;
    $className = substr($extract, strrpos($extract, '/') + 1);

    $dirFromAppApi = realpath($autoload_dir . '/../classes');
    $dirFromCoreApi = realpath($autoload_dir . '/../core/classes');
    $endOfFilename = '.class.php';
    if (file_exists($dirFromAppApi . $classFolder . '/' . $className . $endOfFilename)) {
        require_once $dirFromAppApi . $classFolder . '/' . $className . $endOfFilename;
    } elseif (file_exists($dirFromCoreApi . $classFolder . '/' . $className . $endOfFilename)) {
        require_once $dirFromCoreApi . $classFolder . '/' . $className . $endOfFilename;
    }
});
