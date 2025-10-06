<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier est appelé sur la PROD par la DEV pour permettre un rebond SSO.
 */
if (isset($_SERVER) && is_array($_SERVER) && array_key_exists('REQUEST_URI', $_SERVER)) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (php_sapi_name() !== 'cli') {
            $lifetime = 39600; // 11 heures
            $domain =
                strpos($_SERVER['HTTP_ORIGIN'] ?? '', 'localhost') === false
                && array_key_exists('SERVER_NAME', $_SERVER) ?
                $_SERVER['SERVER_NAME'] :
                null;
            session_set_cookie_params(array(
                'lifetime' => $lifetime,
                'path'     => '/',
                'domain'   => $domain,
                'secure'   => true,
                'httponly' => false,
                'samesite' => 'None'
            ));
        }
        session_start();
    }
    if (isset($_GET['callback'])) {
        $_SESSION['callback'] = $_GET['callback'];
    } else {
        unset($_SESSION['callback']);
    }

    session_status() === PHP_SESSION_ACTIVE
        && session_write_close();

    header("Location: ../SSO/");
}
