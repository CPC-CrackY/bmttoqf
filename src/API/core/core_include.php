<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier gère principalement la connection à la base de données.
 * Losque ce fichier est invoqué depuis Apache, il importe 'core_include_http.php'
 */

use EnedisLabBZH\Core\CoreMysqli;
use EnedisLabBZH\Core\CoreException;

try {
    $_minimalSizeToCompress = 800;
    define('APP_NAME', getenv('DB_USER'));

    require_once __DIR__ . '/autoload.php';
    require_once __DIR__ . '/core_functions.php';

    /**
     * Connexion à la base de données MySQL
     */
    $_mysqlServer = getenv('DB_HOST');
    $_mysqlDatabase = getenv('DB_NAME');
    $_mysqlLogin = getenv('DB_USER');
    $_mysqlPassword = getenv('DB_PASSWORD');
    $_origin = getenv('BASE_URL');

    /**
     * For new apps, please use CoreMysqli singleton
     */
    if (!CoreMysqli::init($_mysqlServer, $_mysqlLogin, $_mysqlPassword, $_mysqlDatabase)) {
        throw new CoreException(__FILE__ . ':' . __LINE__ . ' : (error) new CoreMysqli(...)');
    }
    if (!CoreMysqli::get()->query('SET NAMES \'utf8\'')) {
        throw new CoreException(__FILE__ . ':' . __LINE__ . ' : Erreur SET NAMES');
    }
    CoreMysqli::get()->query('SET time_zone = "Europe/Paris"');
    // CoreMysqli::get()->report_mode = MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX;

    /**
     * For old apps, we still intitiate deprecated $_dbh
     */
    $_dbh = mysqli_connect($_mysqlServer, $_mysqlLogin, $_mysqlPassword);
    if (!$_dbh) {
        throw new CoreException(__FILE__ . ':' . __LINE__ . ' : (error) mysqli_connect(...)');
    }
    if (!mysqli_select_db($_dbh, $_mysqlDatabase)) {
        throw new CoreException(__FILE__ . ':' . __LINE__ . ' : Erreur mysqli_select_db()');
    }
    if (!mysqli_query($_dbh, 'SET NAMES \'utf8\'')) {
        throw new CoreException(__FILE__ . ':' . __LINE__ . ' : Erreur SET NAMES');
    }
    mysqli_query($_dbh, 'SET time_zone = "Europe/Paris"');

    // Active error mysqli
    mysqli_report(MYSQLI_REPORT_ALL & ~MYSQLI_REPORT_INDEX);

    unset($_mysqlServer);
    unset($_mysqlDatabase);
    unset($_mysqlLogin);
    unset($_mysqlPassword);
    unset($_origin);

    /**
     * Permet de fixer le niveau de rapport d'erreur de PHP.
     */
    if (isRuningOnDevServer()) {
        error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(E_ERROR);
        ini_set('error_reporting', E_ERROR);
        ini_set('display_errors', 0);
    }

    if (php_sapi_name() !== 'cli') {
        require_once __DIR__ . '/core_include_http.php';
    }
} catch (Exception $e) {

    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    die();
}

define('BACKEND_VERSION', '5.8.3');
