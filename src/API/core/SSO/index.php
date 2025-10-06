<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier est notre URL de déclenchement (et de callback) pour la connexion SSO.
 */
if (isset($_SERVER) && is_array($_SERVER) && array_key_exists('OIDC_access_token', $_SERVER)) {
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
    $url = str_replace('API/core/SSO/redirect', '', $_SERVER['OPENID_REDIRECT_URI']);
    if (isset($_SESSION) && array_key_exists('callback', $_SESSION)) {
        // Dans le cas d'une délégation SSO, on renvoie vers l'app demandeuse.
        $url = $_SESSION['callback'];
        unset($_SESSION['callback']);
    }
    session_status() === PHP_SESSION_ACTIVE
        && session_write_close();

    $access_token = $_SERVER['OIDC_access_token'];
    header("Location: {$url}#/sso?token=" . base64_encode($access_token));
}
