<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier gère principalement les headers HTTP.
 * Losque un script php est invoqué depuis Apache, celui-ci est importé par 'core_include.php'
 */

use EnedisLabBZH\Core\CoreMysqli;

header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');

$allowOrigin = "*";

if (!isRuningOnDevServer()) {
    if (isset($_SERVER) && is_array($_SERVER) && array_key_exists('HTTP_ORIGIN', $_SERVER)) {
        $http_origin = $_SERVER['HTTP_ORIGIN'];
    } elseif (isset($_SERVER) && is_array($_SERVER) && array_key_exists('HTTP_REFERER', $_SERVER)) {
        $parse = parse_url($_SERVER['HTTP_REFERER']);
        $http_origin = $parse['scheme'] . "://" . $parse['host'];
    }
    if (isset($_origin) && is_array($_origin)) {
        if (in_array($http_origin, (array) $_origin)) {
            $allowOrigin = $http_origin;
        } else {
            $allowOrigin = "http://example.com";
        }
    } else {
        if (isset($_origin) && isset($http_origin) && ($_origin === $http_origin)) {
            $allowOrigin = $_origin;
        } else {
            $allowOrigin = "http://example.com";
        }
    }
} elseif (
    isRuningOnDevServer()
    && isset($_SERVER)
    && is_array($_SERVER)
    && array_key_exists('HTTP_REFERER', $_SERVER)
    && strpos($_SERVER['HTTP_REFERER'], 'localhost') !== false
) {
    if (isset($_SERVER) && is_array($_SERVER) && array_key_exists('HTTP_REFERER', $_SERVER)) {
        $allowOrigin = substr($_SERVER['HTTP_REFERER'], 0, -1);
    } elseif (isset($_origin)) {
        $allowOrigin = $_origin;
    } else {
        $allowOrigin = "http://localhost:4200";
    }
} else {
    if (isset($_SERVER) && is_array($_SERVER) && array_key_exists('HTTP_ORIGIN', $_SERVER)) {
        $allowOrigin = $_SERVER['HTTP_ORIGIN'];
    } elseif (isset($_SERVER) && is_array($_SERVER) && array_key_exists('HTTP_REFERER', $_SERVER)) {
        $allowOrigin = substr($_SERVER['HTTP_REFERER'], 0, -1);
    } else {
        $allowOrigin = "*";
    }
}

$isExternalApi = false;
if (
    isset($_externalApis)
    && (
        (isset($_GET['subject']) && in_array($_GET['subject'], $_externalApis))
        || (isset($_GET['sujet']) && in_array($_GET['sujet'], $_externalApis))
    )
) {
    $allowOrigin = $_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : '*';
    $isExternalApi = true;
}

header("Access-Control-Allow-Origin: " . $allowOrigin);
header("Access-Control-Allow-Credentials: true");

header("Access-Control-Expose-Headers: X-Version");
!headers_sent()
    && !$isExternalApi
    && header('X-Version: ' . getFrontendJsVersion());

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    /**
     * J'envoie les headers utilisés par les API (CORS).
     */
    header("Access-Control-Allow-Origin: $allowOrigin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Methods: GET, POST, HEAD, OPTIONS, PUT, DELETE");
    $AccessControlAllowHeaders =
        'Origin,'
        . ' X-Requested-With,'
        . ' Content-Type,'
        . ' Authorization,'
        . ' Accept,'
        . ' Access-Control-Request-Method';
    header('Access-Control-Allow-Headers: ' . $AccessControlAllowHeaders);
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    die();
} else {
    /**
     * La page ne sera pas enregistrée dans le cache.
     */
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Mon, 23 Jun 2003 18:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}
$lifetime = 39600; // 11 heures
$parse_url = parse_url($_SERVER['REQUEST_URI']);
$path = $parse_url['path'];
$base_path = str_replace('/API/', '/', $path);
$domain = !isLocalhost() && array_key_exists('SERVER_NAME', $_SERVER) ? $_SERVER['SERVER_NAME'] : null;
session_set_cookie_params(array(
    'lifetime' => $lifetime,
    'path'     => $base_path,
    'domain'   => $domain,
    'secure'   => true,
    'httponly' => false,
    'samesite' => 'None'
));

$headers = getallheaders();
$accessToken =
    array_key_exists('authorization', $headers)
    ? trim(substr(@$headers['authorization'], 7))
    : null;
$accessToken =
    ($accessToken === null) && array_key_exists('Authorization', $headers)
    ? trim(substr(@$headers['Authorization'], 7))
    : $accessToken;
if ($accessToken) {
    createCacheTableIfNotExists();
    /**
     * L'access_token a-t-il été mis en cache ?
     */
    $stmt = CoreMysqli::get()
        ->prepare(
            "SELECT
            *,
            `date` <= CURDATE() - INTERVAL 11 HOUR AS `expired`
            FROM `{$_tablesPrefix}core_cache`
            WHERE `access_token` = ?"
        );
    $stmt->bind_param('s', $accessToken);
    $stmt->execute();
    $rows = $stmt->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    CoreMysqli::get()
        ->query("DELETE FROM `{$_tablesPrefix}core_cache` WHERE `date` <= CURDATE() - INTERVAL 11 HOUR");

    $user = null;

    $rows
        && isset($rows[0])
        && $user = $rows[0];

    if (
        isset($user)
        && is_array($user)
        && array_key_exists('expired', $user)
        && $user['expired'] === '1'
    ) {
        !headers_sent() && header('Temporary-Header: True', true, 401);
        header_remove('Temporary-Header');
        http_response_code(401);
        die();
    }

    if (!isset($user)) {
        // NON : on récupère le user depuis Gardian
        $headers = array('Authorization: Basic ' . $accessToken);

        $webssoServer = str_replace('rec-websso', 'websso', $_SERVER['OPENID_URL_GARDIAN_WEBSSO']);
        !$webssoServer && $webssoServer = "websso-gardian.myelectricknetwork.com";
        $url = "https://{$webssoServer}/gardianwebsso/oauth2/multiauth/userinfo";
        $user = json_decode(httpGet($url, false, 1, null, null, $headers), true);
        if (!$user || !is_array($user) || !array_key_exists('sn', $user)) {
            $url = "https://rec-websso-gardian.myelectricknetwork.com/gardianwebsso/oauth2/multiauth/userinfo";
            $user = json_decode(httpGet($url, false, 1, null, null, $headers), true);
        }

        if (!$user || !is_array($user) || !array_key_exists('sn', $user)) {
            // Si user n'existe pas : http_return_code(401) => /login
            !headers_sent() && header('Temporary-Header: True', true, 401);
            header_remove('Temporary-Header');
            http_response_code(401);
            die();
        }
        $user['givenName']  = @$user['givenName'] ? $user['givenName'] : '';
        $user['departmentNumber']  = @$user['departmentNumber'] ? $user['departmentNumber'] : '';
        $user['gardianHierarchie']  = @$user['gardianHierarchie'] ? $user['gardianHierarchie'] : '';
        $user['gardianSesameProfil']  = @$user['gardianSesameProfil'] ? json_encode($user['gardianSesameProfil']) : '';
        // Et on l'insère dans le cache
        $stmt = CoreMysqli::get()->prepare(
            "INSERT INTO `{$_tablesPrefix}core_cache`
            (
                `access_token`,
                `uid`,
                `mail`,
                `sn`,
                `givenName`,
                `departmentNumber`,
                `gardianHierarchie`,
                `gardianSesameProfil`,
                `date`
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                `access_token` = VALUES(`access_token`),
                `mail` = VALUES(`mail`),
                `sn` = VALUES(`sn`),
                `givenName` = VALUES(`givenName`),
                `departmentNumber` = VALUES(`departmentNumber`),
                `gardianHierarchie` = VALUES(`gardianHierarchie`),
                `gardianSesameProfil` = VALUES(`gardianSesameProfil`),
                `date` = NOW()"
        );
        $stmt->bind_param(
            'ssssssss',
            $accessToken,
            $user['uid'],
            $user['mail'],
            $user['sn'],
            $user['givenName'],
            $user['departmentNumber'],
            $user['gardianHierarchie'],
            $user['gardianSesameProfil']
        );
        $stmt->execute();
    }
    if (isset($user) && is_array($user) && array_key_exists('uid', $user)) {
        $_USER = [
            'access_token' => $accessToken,
            'nni' => @$user['uid'],
            'email' => @$user['mail'],
            'nom' => @$user['sn'],
            'prenom' => @ucwords(strtolower($user['givenName'])),
            'fsdum' => @$user['departmentNumber'] ? $user['departmentNumber'] : '',
            'organisation' => @$user['gardianHierarchie'] ? $user['gardianHierarchie'] : '',
            'gardianSesameProfil' => @$user['gardianSesameProfil'] ? json_decode($user['gardianSesameProfil']) : '',
            'grants' => @$user['grants'] ? json_decode($user['grants']) : []
        ];
        $_nni = @$user['uid'];
    }
    if (isset($_USER)) {
        foreach ($_USER as $key => $variable) {
            eval("\$_{$key} = \$_USER['{$key}'];");
        }
    }
}

session_status() !== PHP_SESSION_ACTIVE
    && session_start();
session_status() === PHP_SESSION_ACTIVE
    && session_write_close();
