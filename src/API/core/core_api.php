<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier contient les API du squelette, la gestion du chiffrement front/back et la gestion des erreurs.
 * Toutes les API situées avant la ligne contenant "require_once './my-api.php';" ne peuvent être écrasées par votre application.
 * A contrario, toutes les API situées après la ligne contenant "require_once './my-api.php';"
 * sont prévues pour être écrasées par votre application, en les déclarant dans my-api.php
 * (rgpd, leftFooter, rightFooter, fileUpload...)
 */

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreParameters;
use EnedisLabBZH\Core\Apigile\ApiContacts;
use EnedisLabBZH\Core\CoreEvent;
use EnedisLabBZH\Core\CoreMysqli;
use EnedisLabBZH\Core\GitlabApi;
use EnedisLabBZH\Core\GitlabApi\GitlabProject;

try {
    // Importation de l'autoload Composer si un fichier composer.json a généré l'import de bibliothèques PHP.
    file_exists(__DIR__ . '/../../vendor/autoload.php')
        && require_once __DIR__ . '/../../vendor/autoload.php';

    require_once dirname(__FILE__) . '/../my-config.inc.php';
    !isset($_externalApis) && $_externalApis = [];
    $_externalApis = array_merge($_externalApis, array('getParameter', 'getLastMetrics', 'getExternalApis', 'getScope'));
    require_once dirname(__FILE__) . '/core_include.php';
    require_once dirname(__FILE__) . '/core_functions.php';

    !headers_sent() && header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');

    define('TEMPORARY_HEADER_TRUE', 'Temporary-Header: True');
    define('CURLOPT_SVP', CURLOPT_SSL_VERIFYPEER);
    define('CURLOPT_SVH', CURLOPT_SSL_VERIFYHOST);

    /**
     * Cette ligne ajoute les arguments (en ligne de commande) au tableau $_GET[]
     * Ceci permet de renseigner les arguments dans le crom du serveur.
     * Par exemple, pour appeler importation.php?optimise=oui&ferme=oui
     * Il faut insérer dans le fichier crontab :
     *     php -f importation.php optimise=oui ferme=oui
     */
    if (isset($argv) && is_array($argv)) {
        @parse_str(implode('&', array_slice($argv, 1)), $_GET2);
        $_REQUEST = array_merge($_REQUEST, $_GET2);
    }

    /**
     * AngularJS n'utilise pas le même format d'encodage des champs de formulaire que PHP.
     * La ligne suivante permet de récupérer les paramêtres envoyés par AngularJS
     *
     * A voir s'il est nécessaire de conserver cela avec Angular ???
     */
    $parameters = @json_decode(file_get_contents('php://input'), true);
    null == $parameters
        && $parameters = $_REQUEST;

    foreach ($_COOKIE as $name => $value) {
        $parameters[$name] = $value;
    }

    if (isset($parameters['request'])) {
        if (!array_key_exists('handshake', $_SESSION)) {
            session_status() === PHP_SESSION_ACTIVE
                && session_write_close();
            sleep(3);
            session_status() !== PHP_SESSION_ACTIVE
                && session_start();
            if (!array_key_exists('handshake', $_SESSION)) {
                !headers_sent() && header('Access-Control-Expose-Headers: X-Request');
                !headers_sent() && header('X-Request: ' . $parameters['request']);
                !headers_sent() && header(TEMPORARY_HEADER_TRUE, true, 405);
                header_remove('Temporary-Header');
                http_response_code(405);
                die();
            }
            session_status() === PHP_SESSION_ACTIVE
                && session_write_close();
        }
        // get the encrypted data from client and base 64 decode it
        $log_text = 'json_encode($parameters[request]) = ' . htmlentities(json_encode($parameters['request']));
        isRuningOnDevServer()
            && error_log($log_text);
        $parameters['request']
            && is_array($parameters['request'])
            && isset($parameters['request']['__zone_symbol__value'])
            && $parameters['request'] = $parameters['request']['__zone_symbol__value'];
        $encryptedMsg = base64_decode($parameters['request']);

        // get the first 16 bytes from the payload (must match the IV byte length)
        $iv = mb_substr($encryptedMsg, 0, 16, '8bit');

        // get the encrypted value part (should match the rest of the payload)
        $encrypted = mb_substr($encryptedMsg, 16, null, '8bit');
        // decrypt the value
        $decryptedData = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $_SESSION['handshake']['shared_key'],
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($encryptedMsg !== '' && $decryptedData == '') {
            !headers_sent() && header(TEMPORARY_HEADER_TRUE, true, 405);
            header_remove('Temporary-Header');
            http_response_code(405);
            die();
        }
        isRuningOnDevServer() && error_log('$decryptedData = ' . $decryptedData);
        $decryptedDataObject = json_decode($decryptedData, true);
        $payload = @$decryptedDataObject['subject'] . @$decryptedDataObject['sujet'];
        function urldecodeRecursive($unknown)
        {
            if (is_array($unknown)) {
                foreach ($unknown as $key => $value) {
                    $unknown[$key] = urldecodeRecursive($value);
                }

                return $unknown;
            } else {
                return is_string($unknown) ? urldecode($unknown) : $unknown;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $parameters = $decryptedDataObject;
        } elseif (strpos($payload, '&') === false) {
            $parameters = urldecodeRecursive($decryptedDataObject);
        } else {
            $params = explode('&', $payload);
            $parameters['subject'] = $params[0];
            for ($i = 1; $i < sizeof($params); $i++) {
                $param = explode('=', $params[$i]);
                $parameters[$param[0]] = $param[1];
            }
        }
    }


    /**
     * Je récupère le type d'appel
     */
    $subject = @$parameters['subject'] . @$parameters['sujet'];

    $_perimeterFilter = '';

    /**
     * J'utilise les transactions pour pouvoir tout annuler si une erreur se produit durant le script
     */
    $globalResult = true;
    mysqli_autocommit($_dbh, false);
    CoreMysqli::get()->autocommit(false);

    $JSONoutput = '';
    $has_not_match_a_subject = 0;
    $has_not_reached_first_core_api_section = $has_not_reached_second_core_api_section = false;

    switch ($subject) {
        case 'getExternalApis':
            $JSONoutput = json_encode($_externalApis);

            break;
        case 'getScope':
            $JSONoutput = json_encode(array_key_exists('OPENID_SCOPE', $_SERVER)) ? $_SERVER['OPENID_SCOPE'] : 'no scope';

            break;
        case 'login':
            $alertToDisplay['type'] = 'warning';
            $alertToDisplay['title'] = 'Message pour le développeur';
            $alertToDisplay['message'] = 'Le front-end doit être mis-à-jour !';
            $tempArray['alertToDisplay'] = $alertToDisplay;
            $JSONoutput = json_encode($tempArray);

            break;
        case 'obtainMyGrants':
        case 'getMyGrants':
            updateDatabaseIfNeeded($_dbh, $_tablesPrefix);
            purgeObsoleteUsers();
            updateParametersIfNeeded();
            // obtainMyGrants starts here

            // 4 headers de retour doivent être autorisés : X-Destroy-* permet de ne pas éveiller la curiosité.
            !headers_sent()
                && header('Access-Control-Expose-Headers: '
                    . 'X-Destroy-A, X-Destroy-B, X-Destroy-C, X-Destroy-D, X-Message');
            // Si l'utilisateur est déjà connecté, je récupère ses informations.
            isset($_USER) && array_key_exists('nni', $_USER) && $_USER['nni']
                && $_nni = mysqli_real_escape_string($_dbh, @$_USER['nni']);
            // Création de la listes des NNI des utilisateurs habilités ($globalUsers) et actifs ($activeUsers)
            $query = "SELECT `nni` FROM `{$_tablesPrefix}core_users` "
                . "WHERE `last_successfull_login` > (CURDATE() - INTERVAL 6 MONTH)";
            $activeUsers = '' . @implode(';', array_map(function ($a) {
                return $a['nni'];
            }, mysqli_fetch_all(mysqli_query($_dbh, $query), MYSQLI_ASSOC)));
            $globalUsers = '' . @implode(';', array_map(function ($a) {
                return $a['nni'];
            }, mysqli_fetch_all(mysqli_query($_dbh, "SELECT `nni` FROM `{$_tablesPrefix}core_users`"), MYSQLI_ASSOC)));

            // Calcul des numéros des versions du backend et du frontend
            $frontendVersion = 'v. inconnue';
            $filesInRootFolder = scandir('..');
            $mostRecentModifiedDate = null;
            $mostRecentMainFile = '';
            foreach ($filesInRootFolder as $filename) {
                if (substr($filename, 0, 5) === 'main.' && filemtime('../' . $filename) > $mostRecentModifiedDate) {
                    $mostRecentModifiedDate = filemtime('../' . $filename);
                    $mostRecentMainFile = $filename;
                }
            }
            if ('' !== $mostRecentMainFile) {
                $main = file_get_contents('../' . $mostRecentMainFile);
                $isolate = isolateText($main, '\xa7#', '#\xa7');
                ('' !== $isolate) && $frontendVersion = $isolate;
            }
            // Quel est l'URL ?
            $myUrl = getenv('BASE_URL') . '/' . $_SERVER['REQUEST_URI'];
            $parts = explode('/API', $myUrl);
            $myUrl = $parts[0];
            $env = false !== strpos($myUrl, '-dev.') && false !== strpos($myUrl, '-poc.') ? 'dev' : 'prod';
            $app = str_replace('-dev', '', explode('.', getenv('HOSTNAME'))[0]);
            $postFields =
                "&url=" . urlencode(base64_encode($myUrl))
                . "&env=" . urlencode(base64_encode($env))
                . "&app=" . urlencode(base64_encode($app))
                . "&gu=" . urlencode(base64_encode($globalUsers))
                . "&au=" . urlencode(base64_encode($activeUsers))
                . "&bev=" . urlencode(base64_encode(BACKEND_VERSION))
                . "&fev=" . urlencode(base64_encode($frontendVersion));
            !headers_sent() && @header('X-Destroy-D: ' . base64_encode($postFields));
            if (false === isset($_nni) || false === $_nni || '' === $_nni) {
                // En cas de problème de connexion, je préviens l'utilisateur.
                !headers_sent() && @header('X-Destroy-C: /login');
                !headers_sent() && @header('X-Message: ' . base64_encode(json_encode($_SESSION)));
                displayAlertInFront('ERREUR', "Vous n'êtes pas habilité(e) à utiliser cette application.");
            }
            if (
                isset($_nni)
                && isset($_USER)
                && array_key_exists('email', $_USER)
                && $_USER['email'] !== null
            ) {
                CoreMysqli::get()->query(
                    "UPDATE `{$_tablesPrefix}core_users`
                    SET `email` = '" . mysqli_real_escape_string($_dbh, $_USER['email']) . "'
                    WHERE `nni` = '$_nni'");
            }
            $myRoles = array();
            $tempGrantsArray = array();
            if (isset($visitorGrants) && is_array($visitorGrants)) {
                foreach ($visitorGrants as $grant) {
                    $tempGrantsArray[] = $grant;
                    $perimeters[$grant] = 'ENEDIS';
                }
            }
            $result = mysqli_query($_dbh, "SELECT `roles` FROM `{$_tablesPrefix}core_users` WHERE `nni` LIKE '$_nni'");
            if (($row = mysqli_fetch_assoc($result))) {
                $myRoles = @explode(',', $row['roles']);
                CoreMysqli::get()->query("UPDATE `{$_tablesPrefix}core_users` SET `last_successfull_login` = CURDATE() WHERE `nni` = '$_nni'");
            };
            isset($_USER['gardianSesameProfil'])
                && is_array($_USER['gardianSesameProfil'])
                && is_array($myRoles)
                && $myRoles = array_merge($myRoles, $_USER['gardianSesameProfil']);
            array_key_exists('OIDC_CLAIM_gardianSesameProfil', $_SERVER)
                && is_array($_SERVER['OIDC_CLAIM_gardianSesameProfil'])
                && is_array($myRoles)
                && $myRoles = array_merge($myRoles, $_SERVER['OIDC_CLAIM_gardianSesameProfil']);
            $url = '/apropos';
            $_USER['nni'] = $_nni;
            $redirectUrl = '/';
            isset($visitorBaseUrl)
                && $redirectUrl = $visitorBaseUrl;
            if (isset($grants) && is_array($grants) && sizeof($grants) > 0) {
                foreach ($grants as $role => $grants) {
                    foreach ($myRoles as $myRole) {
                        if ($myRole !== '') { // XXX_ADMIN_1464M => $roleName = ADMIN; $perimeter = 1464M;
                            $roleName = substr($myRole, 0, @strpos($myRole, '_', @strpos($myRole, '_', 1) + 1));
                            $perimeter = substr($myRole, @strpos($myRole, '_', @strpos($myRole, '_', 1) + 1) + 1);
                            $perimeter === '' && $perimeter = 'ENEDIS';
                            if ($roleName === $role) {
                                foreach ($grants as $grant) {
                                    $tempGrantsArray[] = $grant;
                                    $perimeters[$grant] = $perimeter;
                                }
                                $url = $baseUrl[$roleName];
                                $redirectUrl = $url;
                            }
                        }
                    }
                }
            }
            $_USER['grants'] = @$tempGrantsArray;

            isset($_USER['grants'])
                && $_USER['grants'] = array_merge(@$_USER['grants'], @$tempGrantsArray);
            $_USER['grants'] = array_keys(array_flip($_USER['grants']));
            $jsonGrants = json_encode($_USER['grants']);
            $stmt = CoreMysqli::get()->prepare("UPDATE `{$_tablesPrefix}core_cache` SET `grants` = ? WHERE access_token = ?");
            $stmt->bind_param(
                'ss',
                $jsonGrants,
                $_USER['access_token']
            );
            $stmt->execute();

            array_key_exists('grants', $_USER)
                && (
                    !is_countable($_USER['grants'])
                    || (is_countable($_USER['grants']) && count($_USER['grants']) === 0)
                )
                && displayAlertInFront('ERREUR', "Vous n'êtes pas habilité(e) à utiliser cette application.");
            mysqli_query(
                $_dbh,
                "UPDATE `{$_tablesPrefix}core_users`
                SET `last_successfull_login` = CURDATE()
                WHERE `nni` = '$_nni'"
            );
            if (null === @$_USER['fsdum'] && getenv('DB_NAME') === 'wjw') {
                $stmt = CoreMysqli::get()->query("SELECT * FROM `{$_tablesPrefix}population` WHERE `nni` LIKE '$_nni'");
                $rows = $stmt->fetch_all(MYSQLI_ASSOC);
                is_array($rows)
                    && isset($rows[0])
                    && isset($rows[0]['fsdum'])
                    && $_USER['fsdum'] = $stmt->fetch_all(MYSQLI_ASSOC)[0]['fsdum'];
            }
            !headers_sent() && @header('X-Destroy-A: ' . base64_encode(json_encode(array(
                $_USER['nni'],
                $_USER['prenom'],
                $_USER['nom'],
                $_USER['email'],
                $_USER['fsdum']
            ))));
            !headers_sent() && @header('X-Destroy-B: ' . base64_encode(json_encode($_USER['grants'])));
            if (
                isset($_USER)
                && array_key_exists('grants', $_USER)
                && is_countable($_USER['grants'])
                && count($_USER['grants']) > 0
                && ($subject === 'login' || $subject === 'obtainMyGrants' || $_SESSION['mustRedirect'] === true)
            ) {
                isset($redirectUrl)
                    && !headers_sent()
                    && @header('X-Destroy-C: ' . $redirectUrl);
                $_SESSION['mustRedirect'] = false;
            }
            session_status() === PHP_SESSION_ACTIVE
                && session_write_close();
            /**
             * Je convertis le tableau de données au format JSON.
             */
            $JSONResult = json_encode(array('success'));

            break;

        case 'logout':
            $handshake = @$_SESSION['handshake'];
            isset($encryptedKey) && $tempArray['encryptedKey'] = base64_encode($encryptedKey);
            isset($_USER)
                && array_key_exists('accessToken', $_USER)
                && CoreMysqli::get()->query("DELETE FROM `{$_tablesPrefix}core_cache` WHERE `access_token` = '{$_USER['accessToken']}'");
            if (isset($_USER)) {
                unset($_USER);
            }
            session_status() === PHP_SESSION_ACTIVE
                && session_destroy();
            unset($_SESSION);
            $_SESSION = array();
            session_status() !== PHP_SESSION_ACTIVE
                && session_start();
            $_SESSION['handshake'] = $handshake;
            session_status() === PHP_SESSION_ACTIVE
                && session_write_close();
            $JSONoutput = json_encode(array('yes'));

            break;
        case 'handshake':
            $publicKey = $parameters['publicKey'];
            // generate a 32 char random string to be used as shared key
            $sharedKey = bin2hex(openssl_random_pseudo_bytes(16));
            // encrypt the shared key using the client's public key
            $encryptedKey = '';
            openssl_public_encrypt($sharedKey, $encryptedKey, $publicKey, OPENSSL_PKCS1_PADDING);
            session_status() !== PHP_SESSION_ACTIVE
                && session_start();
            $_SESSION['handshake'] = array('encryptedKey' => $encryptedKey, 'shared_key' => $sharedKey);
            session_status() === PHP_SESSION_ACTIVE
                && session_write_close();
            $tempArray['encryptedKey'] = base64_encode($encryptedKey);
            $JSONoutput = json_encode($tempArray);

            break;
        case 'getRoles':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            $JSONoutput = json_encode(mysqli_fetch_all(mysqli_query(
                $_dbh,
                "SELECT DISTINCT * FROM `{$_tablesPrefix}core_roles` ORDER BY `order`, `id`"
            ), MYSQLI_ASSOC));

            break;
        case 'getSanitizedRoles':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            $sanitize = function ($fields) {
                $fields['label'] = substr($fields['label'], strpos($fields['label'], '_') + 1);

                return $fields;
            };
            $JSONoutput = json_encode(array_map($sanitize, mysqli_fetch_all(mysqli_query(
                $_dbh,
                "SELECT DISTINCT * FROM `{$_tablesPrefix}core_roles` ORDER BY `order`, `id`"
            ), MYSQLI_ASSOC)));

            break;
        case 'addRole':
            abortIfNotGranted('ROLES');
            $label = @mysqli_real_escape_string($_dbh, $parameters['label']);
            $description = @mysqli_real_escape_string($_dbh, $parameters['description']);
            $query = "SELECT (`order`+1) AS `nextOrder` FROM `{$_tablesPrefix}core_roles` ORDER BY `order` DESC";
            $result = mysqli_query($_dbh, $query);
            $nextOrder = 0;
            $row = mysqli_fetch_assoc($result)
                && $nextOrder = 0 + @$row['nextOrder'];
            $query = "
            INSERT INTO
                `{$_tablesPrefix}core_roles`
            (`label`, `description`, `order`)
            VALUES
            ('$label', '$description', $nextOrder)
            ";
            $result = mysqli_query($_dbh, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $tempArray[] = $row;
            }
            /**
             * Je convertis le tableau de données au format JSON.
             */
            $JSONoutput = json_encode($tempArray);

            break;

        case 'addUsers':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            if (array_key_exists('users', $parameters) && array_key_exists('roles', $parameters)) {
                $users = $parameters['users'];

                $roles = mysqli_real_escape_string($_dbh, $parameters['roles']);

                $requestComplement = "";
                foreach ($users as $user) {
                    if (
                        array_key_exists('nni', $user)
                        && array_key_exists('first_name', $user)
                        && array_key_exists('last_name', $user)
                    ) {
                        $nni = mysqli_real_escape_string($_dbh, $user['nni']);
                        $firstname = ucwords(strtolower(mysqli_real_escape_string($_dbh, $user['first_name'])));
                        $lastname = mysqli_real_escape_string($_dbh, $user['last_name']);
                        '' !== $requestComplement
                            && $requestComplement .= ", ";
                        $requestComplement .= "('$nni', '$firstname', '$lastname', '$roles', CURDATE())";
                    }
                }
                if ('' !== $requestComplement) {
                    $query = "
                    INSERT INTO
                        `{$_tablesPrefix}core_users`
                    (`nni`, `firstname`, `lastname`, `roles`, `creation_date`)
                    VALUES $requestComplement ON DUPLICATE KEY UPDATE `roles` = VALUES(roles)
                    ";
                    $result = mysqli_query($_dbh, $query);
                }
            }

            break;
        case 'getUsers':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            $query = "
            SELECT DISTINCT
                label
            FROM
                `{$_tablesPrefix}core_roles`
            ORDER BY
                `order`
            ";
            $result = mysqli_query($_dbh, $query);
            $roles = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $roles[] = $row['label'];
            }
            $query = "
            SELECT DISTINCT
                *
            FROM
                `{$_tablesPrefix}core_users`
            ORDER BY
                `lastname`, `firstname`
            ";
            $result = mysqli_query($_dbh, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $row['roles'] .= ',';
                foreach ($roles as $role) {
                    if (strpos($row['roles'], $role . '_') !== false) {
                        $row['grants'][$role] = true;
                        $row['perimeters'][$role] = getPerimeters($role, $row['roles']);
                    } else {
                        $row['grants'][$role] = false;
                        $row['perimeters'][$role] = false;
                    }
                }
                $tempArray[] = $row;
            }
            /**
             * Je convertis le tableau de données au format JSON.
             */
            $JSONoutput = json_encode($tempArray);

            break;
        case 'addUser':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            $nni = @mysqli_real_escape_string($_dbh, $parameters['nni']);
            $firstname = @mysqli_real_escape_string($_dbh, $parameters['firstname']);
            $lastname = @mysqli_real_escape_string($_dbh, $parameters['lastname']);
            $query = "
            INSERT INTO
                `{$_tablesPrefix}core_users`
            (`nni`, `firstname`, `lastname`, `roles`, `creation_date`)
            VALUES
            ('$nni', '$firstname', '$lastname', '', CURDATE())
            ";
            $result = mysqli_query($_dbh, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $tempArray[] = $row;
            }
            /**
             * Je convertis le tableau de données au format JSON.
             */
            $JSONoutput = json_encode($tempArray);

            break;
        case 'saveUsers':
            abortIfNotGranted('HABILITATIONS');
            $users = $parameters['users'];
            foreach ($users as $user) {
                $roles = '';
                foreach ($user['grants'] as $grant => $value) {
                    if ($value) {
                        if ($roles > '') {
                            $roles .= ',';
                        }

                        $roles .= mysqli_real_escape_string($_dbh, $grant);
                    }
                }
                $query =
                    "UPDATE `{$_tablesPrefix}core_users`
                    SET `roles`='$roles'
                    WHERE `nni`='" . mysqli_real_escape_string($_dbh, $user['nni']) . "'";
                $result = mysqli_query($_dbh, $query);
            }

            break;
        case 'saveUser':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            $user = $parameters['user'];
            $nni = mysqli_real_escape_string($_dbh, $user['nni']);
            $roles = mysqli_real_escape_string($_dbh, $user['roles']);
            $query = "UPDATE `{$_tablesPrefix}core_users` SET `roles`='$roles' WHERE `nni`='$nni'";
            $result = mysqli_query($_dbh, $query);

            break;
        case 'removeUser':
            abortIfNotGranted(array('ROLES', 'HABILITATIONS'));
            $user = $parameters['user'];
            $nni = mysqli_real_escape_string($_dbh, $user['nni']);
            $query = "DELETE FROM `{$_tablesPrefix}core_users` WHERE `nni`='$nni'";
            $result = mysqli_query($_dbh, $query);

            break;
        case 'getGranters':
            /**
             * Retourne le tableau des users habilites à la page Habilitations (sauf les développeurs).
             */
            $whereStatement = '';
            foreach ($grants as $role => $rights) {
                if (
                    in_array('HABILITATIONS', $rights)
                    && (strpos($role, 'DEVELOPPEUR') === false)
                    && (strpos($role, 'DEVELOPER') === false)
                ) {
                    '' !== $whereStatement
                        && $whereStatement .= 'OR ';
                    $whereStatement .= '`roles` LIKE \'%' . mysqli_real_escape_string($_dbh, $role) . '%\' ';
                }
            }
            '' === $whereStatement
                && $whereStatement = '0';

            try {
                $query = "
                    SELECT `nni`, `lastname`, `firstname`, `email`
                    FROM  `{$_tablesPrefix}core_users`
                    WHERE `email` IS NOT NULL AND $whereStatement
                    ORDER BY `lastname` ASC";
                $result = mysqli_query($_dbh, $query);
                $JSONoutput = json_encode(mysqli_fetch_all(mysqli_query($_dbh, $query), MYSQLI_ASSOC));
            } catch (CoreException $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
                updateDatabaseIfNeeded($_dbh, $_tablesPrefix);
                $JSONoutput = "[]";
            }

            break;
        case 'getParameters':
            abortIfNotGranted('PARAMETRES');
            /**
             * Retourne le tableau des paramètres applicatifs.
             */
            $appParameters = new CoreParameters();
            $JSONoutput = json_encode(
                $appParameters->getParameters(),
                null !== JSON_THROW_ON_ERROR && JSON_THROW_ON_ERROR
            );

            break;
        case 'saveParameters':
            abortIfNotGranted('PARAMETRES');
            $parameters = $parameters['parameters'];
            $appParameters = new CoreParameters();
            if ($appParameters->saveParameters($parameters)) {
                displaySuccessInFront('YES!', 'Les paramètres ont été modifiés.');
            } else {
                displayAlertInFront('ERREUR', 'Les paramètres n\'ont pas été modifiés.');
            }

            break;
        case 'import_files_list':
            abortIfNotGranted(array('IMPORTS'));
            $query = "
            SELECT DISTINCT
                *
            FROM
                `{$_tablesPrefix}importations`
            ORDER BY
                `order`, `id`
            ";
            $JSONoutput = json_encode(mysqli_fetch_all(mysqli_query($_dbh, $query), MYSQLI_ASSOC));

            break;
        case 'core_file_upload':
            abortIfNotGranted(array('IMPORTS'));
            $file = $_FILES['file'];
            if (strpos($file['name'], '..') !== false || strpos($file['tmp_name'], '..') !== false) {
                throw new CoreException("Line " . __LINE__ . " : security warning!");
            }
            $result = mysqli_query($_dbh, $query);
            $folder = '/var/www/html/files/upload';
            if (!file_exists("{$folder}/.")) {
                mkdir("{$folder}");
                chmod("{$folder}", 0775);
            }
            $uploadedFile = "{$folder}/" . $file['name'];
            $csvFile = "{$folder}/fichier.csv";
            // Récupération du first_header :
            $query = "SELECT `first_header`, `table_name` FROM `{$_tablesPrefix}importations`
                    WHERE id =" . mysqli_real_escape_string($_dbh, $file['id']);
            $result = mysqli_query($_dbh, $query);
            $row = mysqli_fetch_assoc($result);
            if (strpos($row['table_name'], "{$_tablesPrefix}_import_") !== false) {
                throw new CoreException("Unable to upload to that table.");
            }
            $query = 'DROP TABLE IF EXISTS `' . mysqli_real_escape_string($_dbh, $row['table_name']) . '_temp`';
            $first_header = $row['first_header'];
            // Récupération du fichier
            if ($res = move_uploaded_file($file['tmp_name'], $uploadedFile) === false) {
                throw new CoreException("Unable to move uploaded file.");
            }
            // Convertion au format CSV
            if ('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            === $file['type']) {
                // XLSX
                echo 'xlsx';
            } elseif (
                'application/vnd.ms-excel' === $file['type']
                && strpos(strtolower($file['name']), '.xls') !== false
            ) {
                // XLS
                echo 'xls';
            } elseif (
                'application/vnd.ms-excel' === $file['type']
                && strpos(strtolower($file['name']), '.csv') !== false
            ) {
                // CSV
                echo 'csv';
            } else {
                // Erreur
                echo 'Unknown file type';
            }
            // Création de la table vierge à partir des colonnes
            $fh = fopen($csvFile, 'r');
            $createQuery = 'CREATE TABLE `' . mysqli_real_escape_string($_dbh, $file['table_name']) . '_temp` (';
            $nb_lines_to_ignore = 0;
            $found = false;
            while (($line = fgets($fh)) && !$found) {
                $nb_lines_to_ignore++;
                $fields = explode(';', mb_convert_encoding($line, 'UTF-8'));
                if ($fields[0] == $first_header) {
                    $found = true;
                    foreach ($fields as $field) {
                        $createQuery .= " `{$field}` TEXT DEFAULT NULL, ";
                    }
                }
            }
            $createQuery = substr($createQuery, 0, -2);
            $createQuery .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $result = mysqli_query($_dbh, $createQuery);

            // Importation du fichier CSV
            if (version_compare(phpversion(), '6.0.0', '<')) {
                $query = "LOAD DATA INFILE '" . $uplodedFile . "'
                        INTO TABLE `" . mysqli_real_escape_string($_dbh, $row['table_name']) . "`
                        CHARACTER SET LATIN1
                        FIELDS TERMINATED BY ';'
                        ENCLOSED BY '\"'
                        LINES TERMINATED BY '\n'
                        IGNORE $nb_lines_to_ignore ROWS;";
            } else {
                $query = "LOAD DATA LOCAL INFILE '" . $uplodedFile . "'
                        INTO TABLE `" . mysqli_real_escape_string($_dbh, $row['table_name']) . "`
                        CHARACTER SET LATIN1
                        FIELDS TERMINATED BY ';'
                        ENCLOSED BY '\"'
                        LINES TERMINATED BY '\n'
                        IGNORE $nb_lines_to_ignore ROWS;";
            }
            $result = mysqli_query($_dbh, $query);
            $query = "DROP TABLE IF EXISTS `" . mysqli_real_escape_string($_dbh, $row['table_name']) . "_backup`";
            $result = mysqli_query($_dbh, $query);
            $query = "RENAME `" . mysqli_real_escape_string($_dbh, $row['table_name'])
                . "` TO `" . mysqli_real_escape_string($_dbh, $row['table_name']) . "_backup`";
            $result = mysqli_query($_dbh, $query);
            $query = "RENAME `" . mysqli_real_escape_string($_dbh, $row['table_name'])
                . "_temp` TO `" . mysqli_real_escape_string($_dbh, $row['table_name']) . "`";
            $result = mysqli_query($_dbh, $query);
            $query = "UPDATE `{$_tablesPrefix}importations` SET `date_last_successful_import` = CURDATE()
                    WHERE id =" . mysqli_real_escape_string($_dbh, $file['id']);
            $result = mysqli_query($_dbh, $query);

            break;
        case 'backendVersion':
            if (false !== strpos($_SERVER['SERVER_NAME'], '.place-cloud-enedis.fr')) {
                $results = [];

                $appParameters = new CoreParameters();
                $gitlab_zac_api_url = $appParameters->getParameter('gitlab_zac_api_url');
                $gitlab_zac_token = $appParameters->getParameter('gitlab_zac_token', 'Token valide permettant l\'accès aux API du Git de déploiement', true);

                $gh = new GitlabApi();
                $gh->setToken($gitlab_zac_token);
                $gh->setApiUrl($gitlab_zac_api_url);

                $gitlab_zac_project_id = $appParameters->getParameter('gitlab_zac_project_id');
                $project = $gh->getProject($gitlab_zac_project_id);

                $envs = $project->getEnvironments();
                foreach ($envs as $env) {
                    $env['name'] === getenv('ENVIRONMENT')
                        && $id = $env['id'];
                }
                if (!$id) {
                    throw new CoreException("No id found for environment \"" . getenv('ENVIRONMENT') . "\".");
                }

                $environment = $project->getEnvironment($id);
                $dateTime = new DateTime($environment['last_deployment']['created_at']);
                $dateTime->modify('+1 hour'); // Ajoute 1 heure
                $dockerCreationDate = $dateTime->format('d/m/Y à H:i:s');
            } else { // PACO est hébergé en ZCI
                error_log('file to test is ' . realpath(__DIR__ . '/../../index.html'));
                $dockerCreationDate = date('d/m/Y à H:i:s', filemtime(realpath(__DIR__ . '/../../index.html')));
            }
            $JSONoutput = json_encode(array(
                "backendVersion" => BACKEND_VERSION,
                "dockerCreationDate" => $dockerCreationDate,
                "openIdEnabled" => getenv('OPENID_ENABLED')
            ));

            break;
        case 'getGitlabApiLastDeployment':

            $created_at = '';
            $updated_at = '';
            $status = '';

            if (false !== strpos($_SERVER['SERVER_NAME'], '.place-cloud-enedis.fr')) {
                $appParameters = new CoreParameters();
                $gitlab_zac_api_url = $appParameters->getParameter('gitlab_zac_api_url', 'Url de base de l\'API du serveur du Git de déploiement (ZAC).');
                $gitlab_zac_token = $appParameters->getParameter('gitlab_zac_token', 'Token valide permettant l\'accès aux API du Git de déploiement', true);

                $gh = new GitlabApi();
                $gh->setToken($gitlab_zac_token);
                $gh->setApiUrl($gitlab_zac_api_url);

                $gitlab_zac_project_id = $appParameters->getParameter('gitlab_zac_project_id');
                $project = new GitlabProject($gh, $gitlab_zac_project_id);

                $envs = $project->getEnvironments();
                foreach ($envs as $env) {
                    $env['name'] === getenv('ENVIRONMENT')
                        && $id = $env['id'];
                }
                if (!$id) {
                    throw new CoreException('no id found');
                }

                $environment = $project->getEnvironment($id);
                $created_at = $environment['last_deployment']['created_at'];
                $updated_at = $environment['last_deployment']['updated_at'];
                $status = $environment['last_deployment']['status'];
            }
            $JSONoutput = json_encode(array(
                'created_at' => $created_at,
                'updated_at' => $updated_at,
                'status' => $status
            ));

            break;
        case 'getApiCallsFromDate':
            $arr = [];
            $query = "SELECT * FROM {$_tablesPrefix}core_log_api WHERE date BETWEEN ? AND CURDATE() - INTERVAL 1 DAY";
            if ($stmt = $_dbh->prepare($query)) {
                $stmt->bind_param("s", $parameters['from']);
                $stmt->execute();
                $result = $stmt->get_result();
                $arr = mysqli_fetch_all($result, MYSQLI_NUM);
                $stmt->close();
            }
            $JSONoutput = json_encode($arr);

            break;
        case 'storeEvent':
            $coreEvent = new CoreEvent(CoreMysqli::get(), $_tablesPrefix);
            $event = [
                'data'        => '' . @$parameters['data'],
                'type'        => '' . @$parameters['type']
            ];
            $res = $coreEvent->storeEvent($event);
            $JSONResult = json_encode(array($res));
            unset($event, $res);

            break;
        case 'getParameter':
            /**
             * Retourne le tableau des paramètres applicatifs.
             */
            if (isset($parameters['appliStats']) && isset($parameters['parameter'])) {
                $appParameters = new CoreParameters();
                !is_array($parameters['parameter']) && $parameters['parameter'] = [$parameters['parameter']];
                foreach ($parameters['parameter'] as $parameter) {
                    $tempArray[$parameter] = $appParameters->getParameter($parameter);
                }
                $JSONoutput = json_encode($tempArray);
            }

            break;
        case 'getLastMetrics':
            /**
             * Retourne le tableau des usages cpu, mémoire et disque.
             */
            if (CoreMysqli::get()->tableExists("{$_tablesPrefix}core_metrics")) {
                $stmt = CoreMysqli::get()->query("
                    SELECT
                        *
                    FROM
                        `{$_tablesPrefix}core_metrics`
                    ORDER BY
                        `date_time` DESC
                    LIMIT 0,1
                    ");
                $JSONoutput = json_encode($stmt->fetch_all(MYSQLI_ASSOC)[0]);
            } else {
                $JSONoutput = json_encode([]);
            }

            break;
        case 'getTodayMetrics':
            /**
             * Retourne le tableau des usages cpu, mémoire et disque.
             */
            if (CoreMysqli::get()->tableExists("{$_tablesPrefix}core_metrics")) {
                $query =
                    "SELECT
                        DATE_FORMAT(`date_time`, '%H:%i') as `label`,
                        `cpu_percent`,
                        `memory_percent`,
                        `disk_percent`
                    FROM
                        `{$_tablesPrefix}core_metrics`
                    WHERE
                        DATE(`date_time`) = CURDATE()
                    ORDER BY
                        `date_time`";
                $JSONoutput = json_encode(mysqli_fetch_all(mysqli_query($_dbh, $query), MYSQLI_ASSOC));
            } else {
                $JSONoutput = json_encode([]);
            }

            break;
        case 'get90DaysMetrics':
            /**
             * Retourne le tableau des usages cpu, mémoire et disque.
             */
            if (CoreMysqli::get()->tableExists("{$_tablesPrefix}core_metrics")) {
                $query =
                    "SELECT
                        DATE_FORMAT(`date`, '%Y-%m-%d') as `label`,
                        `cpu_percent`,
                        `memory_percent`,
                        `disk_percent`
                    FROM
                        `{$_tablesPrefix}core_metrics_history`
                    WHERE
                        `date` >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                    ORDER BY
                        `date`";
                $JSONoutput = json_encode(mysqli_fetch_all(mysqli_query($_dbh, $query), MYSQLI_ASSOC));
            } else {
                $JSONoutput = json_encode([]);
            }

            break;
        case 'getTablesToExport':
            abortIfNotGranted('PARAMETRES');
            /**
             * Retourne la iste des tables à exporter et la configuration des DCP à anonymiser.
             */
            if (CoreMysqli::get()->tableExists("{$_tablesPrefix}core_tables_to_export")) {
                $db_name = getenv('DB_NAME');
                $query =
                    "SELECT t.TABLE_NAME AS `name`, (e.name IS NOT NULL) AS `checked`,
                    (SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'name', c.COLUMN_NAME,
                            'anonymize', IFNULL(a.anonymize, FALSE),
                            'anonymizationMethod', a.anonymizationMethod,
                            'anonymizationConfig', a.anonymizationConfig
                        )
                    )
                    FROM INFORMATION_SCHEMA.COLUMNS c
                    LEFT JOIN `{$db_name}`.`{$_tablesPrefix}core_fields_anonymization` a
                        ON c.TABLE_NAME = a.table_name AND c.COLUMN_NAME = a.field_name
                    WHERE c.TABLE_SCHEMA = '{$db_name}' AND c.TABLE_NAME = t.TABLE_NAME
                    ) AS `fields`
                    FROM INFORMATION_SCHEMA.TABLES t
                    LEFT JOIN `{$db_name}`.`{$_tablesPrefix}core_tables_to_export` e ON t.TABLE_NAME = e.name
                    WHERE t.TABLE_SCHEMA = '{$db_name}'
                    AND t.TABLE_NAME LIKE '{$_tablesPrefix}%'
                    AND t.TABLE_NAME NOT LIKE '%core_log_api'
                    AND t.TABLE_NAME NOT LIKE '%core_metrics'
                    AND t.TABLE_NAME NOT LIKE '%core_metrics_history'
                    AND t.TABLE_NAME NOT LIKE '%core_parameters'
                    AND t.TABLE_NAME NOT LIKE '%core_tables_to_export'
                    AND t.TABLE_NAME NOT LIKE '%core_fields_anonymization'
                    AND t.TABLE_NAME NOT LIKE '%core_substitutions'
                    ";
                $rows = mysqli_fetch_all(mysqli_query($_dbh, $query), MYSQLI_ASSOC);
                foreach ($rows as &$row) {
                    $row['checked'] = $row['checked'] == 1;
                    $row['fields'] = json_decode($row['fields'], true);
                }
                $JSONoutput = json_encode($rows);
            } else {
                $JSONoutput = json_encode([]);
            }

            break;
        case 'saveTablesToExport':
            abortIfNotGranted('PARAMETRES');
            $tables = $parameters['tables'];

            // Effacer les anciennes configurations
            CoreMysqli::get()->query("DELETE FROM `{$_tablesPrefix}core_fields_anonymization`");
            CoreMysqli::get()->query("DELETE FROM `{$_tablesPrefix}core_tables_to_export`");

            // Préparer les requêtes d'insertion
            $stmtTables = CoreMysqli::get()->prepare("INSERT INTO `{$_tablesPrefix}core_tables_to_export` (`name`, `checked`) VALUES (?, ?)");
            $stmtFields = CoreMysqli::get()->prepare(
                "INSERT INTO `{$_tablesPrefix}core_fields_anonymization`
                (`table_name`, `field_name`, `anonymize`, `anonymizationMethod`, `anonymizationConfig`)
                VALUES
                (?, ?, ?, ?, ?)"
            );

            foreach ($tables as $table) {
                if ($table['checked']) {
                    // Insérer la table dans core_tables_to_export
                    $stmtTables->bind_param('si', $table['name'], $table['checked']);
                    $stmtTables->execute();

                    // Insérer les champs à anonymiser pour cette table
                    if (isset($table['fields'])) {
                        foreach ($table['fields'] as $field) {
                            if (isset($field['anonymize']) && $field['anonymize']) {
                                $anonymizationConfig = json_encode($field['anonymizationConfig']);
                                $stmtFields->bind_param('sssss', $table['name'], $field['name'], $field['anonymize'], $field['anonymizationMethod'], $anonymizationConfig);
                                $stmtFields->execute();
                            }
                        }
                    }
                }
            }

            $stmtTables->close();
            displaySuccessInFront('Information', 'La liste des tables à exporter sur le DEV a été mises à jour.');

            break;
        default:
            $has_not_match_a_subject++;
            $has_not_reached_first_core_api_section = true;

            break;
    }

    session_write_close();

    require_once './my-api.php';

    switch ($subject) {
        case 'agentDirectorySearch':
            if ($JSONoutput == '') {
                !isset($_USER['grants']) || count($_USER['grants']) < 1
                    && abortIfNotGranted('%');
                $search = $parameters['search'];
                if (
                    is_string($search)
                    && (strlen($search) < 3 || (preg_match('~\d+~', $search) && strlen($search) !== 6 && strlen($search) !== 8))
                ) {
                    $JSONoutput = json_encode(array());

                    break;
                }
                $JSONoutput = '';

                try {
                    $apiContact = new ApiContacts();
                    !isset($parameters['all'])
                        && $apiContact->selectDR(getMyUm()['um_label']);
                    $employees = $apiContact->search($search);
                    $apiContact->formatAsArray($employees);
                    $JSONoutput = json_encode($employees);
                } catch (CoreException $e) {
                    if ($e->getMessage() === 'error: Oauth2 json returned code 403!') {
                        displayAlertInFront(__FILE__, "Cette application n'est pas raccordée à l'API annuaire !");
                    } elseif ($e->getMessage() === 'error: Oauth2 json returned code 503!') {
                        displayAlertInFront(__FILE__, "Cette application ne peut pas requêter l'API annuaire !");
                    } else {
                        throw new CoreException($e->getMessage());
                    }
                }
            }

            break;
        case 'getDomains':
            if ($JSONoutput == '') {
                $myUM = getMyUm();
                $hierarchy = getMyDRHierarchy(4, true);
                $tree = buildTree($hierarchy, $myUM['code_um'], 'organisation_id', 'parent_organisation_id', 'childs');
                function flattenOrganisation($org, $level = 1)
                {
                    $result = [];
                    $indent = str_repeat("\xC2\xA0", $level * 4); // Espace insécable UTF-8

                    $result[] = ['code_unite' => $org['organisation_id'], 'libelle' => $indent . $org['name']];

                    if (isset($org['childs']) && is_array($org['childs'])) {
                        foreach ($org['childs'] as $child) {
                            $result = array_merge($result, flattenOrganisation($child, $level + 1));
                        }
                    }

                    return $result;
                }
                $tempArray = [[
                    'code_unite' => $myUM['code_um'],
                    'libelle' => $myUM['um_label']
                ]];
                foreach ($tree as $org) {
                    $tempArray = array_merge($tempArray, flattenOrganisation($org));
                }
                $JSONoutput = json_encode($tempArray);
            }

            break;
        case 'rgpd':
            if ($JSONoutput == '') {
                // Ce test permet de ne pas écraser l'éventuel traitement du case 'rgpd' dans ../my-api.php
                $tempArray['content'] = "
                Conformément à la loi n°78-17 du 6 janvier 1978 modifiée
                et au règlement (UE) n°2016/679 du 27 avril 2016,
                les informations recueillies sont enregistrées dans un fichier informatisé
                par ENEDIS - DR Bretagne en sa qualité de responsable de traitement
                dans le cadre ...[A adapter en fonction des cas :
                                    - dans le cadre de l’exécution du contrat … »
                                    ou « dans le cadre de la loi … »
                                    ou « dans le cadre de la mission de service public »
                                    ou dans le cadre « du recueil de vote consentement » etc.]
                pour ... [préciser la finalité du traitement, le but de la collecte des DCP].
                Elles sont conservées pendant... [Préciser la durée de conservation des données]
                et sont destinées à [indiquer la liste des destinataires interne ENEDIS + externes
                (éventuels prestataires) auxquels les DCP sont transmises].
                Vous disposez d’un droit d'accès à vos données, de rectification, d’opposition
                et d’effacement pour motifs légitimes.
                Vous disposez, également, d’un droit à la limitation du traitement
                et à la portabilité des données à caractère personnel vous concernant.
                Vous pouvez exercer vos droits par courrier à l’adresse suivante :
                ENEDIS - DR Bretagne - Equipe numérique - 62 boulevard Voltaire - 35000 RENNES.
                Votre courrier doit préciser votre nom et prénom ainsi que votre NNI.
                Conformément à la loi « informatique et libertés », vous disposez de la faculté
                d’introduire une réclamation auprès de la CNIL.";
                /**
                 * Je convertis le tableau de données au format JSON.
                 */
                $JSONoutput = json_encode($tempArray);
            }

            break;

        case 'leftFooter':
            if ($JSONoutput == '') {
                // Ce test permet de ne pas écraser l'éventuel traitement du case 'leftFooter' dans ../my-api.php
                // $tempArray['content'] =  '<a href="#/mentions" class="text-white">Mentions légales © Enedis '
                // . date("Y", filemtime(__FILE__)) . '</a>';
                $tempArray['content'] = '';
                /**
                 * Je convertis le tableau de données au format JSON.
                 */
                $JSONoutput = json_encode($tempArray);
            }

            break;

        case 'rightFooter':
            if ($JSONoutput == '') {
                // Ce test permet de ne pas écraser l'éventuel traitement du case 'rightFooter' dans ../my-api.php
                $tempArray['content'] = '<a href="#/plan" class="text-white">Plan du site</a>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#/about" class="text-white">A propos &amp; Contact</a>';
                /**
                 * Je convertis le tableau de données au format JSON.
                 */
                $JSONoutput = json_encode($tempArray);
            }

            break;
        case 'importFileTypes':
        case 'lastFileImports':
            if ($JSONoutput == '') {
                // Ce test permet de ne pas écraser l'éventuel traitement du case 'importFileTypes' dans ../my-api.php
                abortIfNotGranted(array('IMPORTS'));
                $query = "
                SELECT
                    `table`,
                    `description`,
                    MAX(`lastImportDate`)
                FROM
                    `{$_tablesPrefix}core_file_imports`
                GROUP BY
                    `table`
                ";
                $stmt = CoreMysqli::get()->prepare($query);
                $stmt->execute();
                $JSONoutput = json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_NUMERIC_CHECK);

                break;
            }

            break;
        case 'fileUpload':
            abortIfNotGranted('IMPORTS');
            $table = $parameters['table'];
            $file = $_FILES['file'];
            // importFileInTable() sera redéfinie dans my-functions.php pour intégrer votre propre traitement.
            $res = importFileInTable($file, $table);
            $JSONoutput = json_encode(array($res));

            break;
        default:
            $has_not_match_a_subject++;
            $has_not_reached_second_core_api_section = true;

            break;
    }

    !isset($_nb_api_files)
        && $_nb_api_files = 1;

    if ($has_not_match_a_subject == (2 + $_nb_api_files)) {
        $log_text = 'SUBJECT NON TROUVÉ / $parameters = '
            . htmlentities(json_encode($parameters, JSON_PRETTY_PRINT), ENT_COMPAT);
        error_log($log_text);
        !headers_sent() && @header(TEMPORARY_HEADER_TRUE, true, 400);
        header_remove('Temporary-Header');
        http_response_code(400);
        die();
    } elseif ($has_not_reached_first_core_api_section && $has_not_reached_second_core_api_section) {
        is_string($subject)
            && $result = registerApiCall($subject, true);
    }
    $textOutput = ob_get_contents();
    @ob_end_clean();

    if ($JSONoutput != '') {
        !headers_sent() && @header('Content-Type: application/json');
        /**
         * J'active la compression du transfert si le texte retourné dépasse "$_minimalSizeToCompress octets".
         */
        if (strlen($JSONoutput) > $_minimalSizeToCompress) {
            ob_start("ob_gzhandler");
        } else {
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            @ini_set('output_handler', '');
            function_exists('apache_setenv') && apache_setenv('no-gzip', 1);
            ob_start();
        }
        /**
         * J'envoie les données au format JSON.
         */
        print $JSONoutput;
        $result2 = @mysqli_commit($_dbh);
        CoreMysqli::get()->commit();
        @ob_end_flush();
    } else {
        @$result2 = mysqli_commit($_dbh);
        @CoreMysqli::get()->commit();
        if ($textOutput != '') {
            /**
             * J'active la compression du transfert si le texte retourné dépasse "$_minimalSizeToCompress octets".
             */
            if (strlen($textOutput) > $_minimalSizeToCompress) {
                ob_start("ob_gzhandler");
            } else {
                @ini_set('zlib.output_compression', 'Off');
                @ini_set('output_buffering', 'Off');
                @ini_set('output_handler', '');
                function_exists('apache_setenv') && apache_setenv('no-gzip', 1);
                ob_start();
            }
            print $textOutput;
            @ob_end_flush();
        } else {
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            @ini_set('output_handler', '');
            function_exists('apache_setenv') && apache_setenv('no-gzip', 1);
            ob_start();
            @ob_end_flush();
        }
    }
} catch (CoreException $e) {
    logWithMemoryUsage(__FILE__, $e->getTraceAsString());
    logWithMemoryUsage(__FILE__, $e->getMessage());
    @ob_end_flush();
    @mysqli_rollback($_dbh);
    @CoreMysqli::get()->rollback();

    // Erreur lors du traitement de la requête client :
    is_string($subject)
        && $result = registerApiCall($subject, false);
    $result && mysqli_commit($_dbh);

    if (isset($query)) {
        logWithMemoryUsage(__FILE__, @mb_convert_encoding($query, 'ISO-8859-15'));
    }
    logWithMemoryUsage(__FILE__, @mb_convert_encoding("error : " . $e->getMessage(), 'ISO-8859-15'));
}
@mysqli_close($_dbh);
@CoreMysqli::get()->close();
