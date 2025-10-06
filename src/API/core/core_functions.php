<?php

/**
 * NE PAS TOUCHER CE FICHIER. MERCI !
 * Ce fichier contient les fonctions utilisables par le squelette mais aussi les applications.
 * Si une des fonctions doit être modifiée pour votre application, ne la modifiez pas ici.
 * Ce script est appelé après my-functions.php,
 * et toutes les déclarations de fonction sont précédées de "if (!function_exists('...')) { ... }"
 * Il vous suffit donc de copier une fonction dans le fichier my-functions.php et de l'y modifier.
 */

use EnedisLabBZH\Core\CoreParameters;
use EnedisLabBZH\Core\Apigile\ApiContacts;
use EnedisLabBZH\Core\Apigile\ApiOrganizations;
use EnedisLabBZH\Core\Apigile\ApiMessage;
use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;

define('SERVICE_EMAIL', 'bzh-enedislab-devweb@enedis.fr');

require_once __DIR__ . '/../my-functions.php';


if (!function_exists('flushAll')) {
    /**
     * flushAll allows echo commands to print directly on browser without delay.
     *
     * @return void
     */
    function flushAll()
    {
        @ob_implicit_flush(true);
        while (@ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();
    }
}

if (!function_exists('getIsoDateWithMilliseconds')) {
    /**
     * getIsoDateWithMilliseconds return Javascript-like ISO date
     *
     * @return string Iso date with milliseconds.
     */
    function getIsoDateWithMilliseconds()
    {
        $microtime = microtime(true);
        $seconds = floor($microtime);
        $milliseconds = round(($microtime - $seconds) * 1000);
        // Utiliser str_pad pour ajouter des zéros au début si nécessaire
        $millisecondsPadded = str_pad((string)$milliseconds, 3, '0', STR_PAD_LEFT);

        $date = new DateTime("@$seconds");

        // Définir le fuseau horaire sur UTC
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format('Y-m-d\TH:i:s') . '.' . $millisecondsPadded . 'Z';
    }
}

if (!function_exists('getAppName')) {
    /**
     * getAppName
     *
     * @return string name of the app
     */
    function getAppName()
    {
        $hostname = getenv('HOSTNAME');
        $hostname_prod = str_replace(['-dev' . '.', '-poc' . '.'], '.', $hostname);
        $parts = explode('.', $hostname_prod);
        if ($parts[0]) {
            return $parts[0];
        }

        return getenv('DB_USER');
    }
}

if (!function_exists('getMyDRHierarchy')) {
    /**
     * getMyDRHierarchy returns gardian hierarchy
     *
     * @param  mixed $depth 1 for DUM, 2 for DUM/SDUM, 3 for DUM/SDUM/FSDUM
     * @return array
     */
    function getMyDRHierarchy($depth, $formatAsArray = true)
    {
        $agencies = null;
        $apiOrganizations = new ApiOrganizations();
        $apiOrganizations->selectDR(getMyUm()['um_label']);
        $agencies = $apiOrganizations->getOrganizations($depth);
        $formatAsArray
            && $apiOrganizations->formatAsArray($agencies);

        return $agencies;
    }
}

if (!function_exists('getMyOwnHierarchy')) {
    /**
     * getMyOwnHierarchy returns gardian hierarchy
     *
     * @param  mixed $depth 1 for DUM, 2 for DUM/SDUM, 3 for DUM/SDUM/FSDUM
     * @return array
     */
    function getMyOwnHierarchy($min_depth = 1, $max_depth = 15)
    {
        //min_depths doit être > 0
        global $_tablesPrefix, $_nni;
        $hierarchy = null;
        $contacts = new ApiContacts();
        $employee = $contacts->searchByNNI($_nni);
        if ($employee !== false && isset($employee) && isset($employee->organization) && isset($employee->organization->structure)) {
            $employee_structure = $employee->organization->structure;
            $cur_entry = &$employee_structure;
            $cur_structure_name = null;
            $hierarchy = [];
            $i = 1;
            while ($i <= $max_depth && isset($cur_entry->entry)) {
                if ($i >= $min_depth) {
                    $cur_structure_name = 'structure_name_' . $i;
                    array_push($hierarchy, trim(strval($cur_entry->entry->$cur_structure_name)));
                }
                $cur_entry = &$cur_entry->entry;
                $i++;
            }
        }

        return $hierarchy;
    }
}

if (!function_exists('getMyUm')) {
    /**
     * getMyUm
     * Returns information about the Direction Regionale of tue user
     *
     * @return array(code_um, um_label) for example ['1464M', 'DR BRETAGNE']
     */
    function getMyUm()
    {
        global $_nni;
        $um_label = '';
        $code_um = '';
        $contacts = new ApiContacts();
        $employees = [$contacts->searchByNNI($_nni)];
        $contacts->formatAsArray($employees);
        if (
            is_array($employees)
            && (count($employees) === 1)
            && is_array($employees[0])
            && array_key_exists('um_label', $employees[0])
        ) {
            $um_label = $employees[0]['um_label'];
            $code_um = $employees[0]['code_um'];
        }

        return ['code_um' => $code_um, 'um_label' => $um_label];
    }
}

if (!function_exists('isConnectedToWebSSO')) {
    /**
     * isConnectedToWebSSO
     *
     * @return boolean true if this app runs on a Place v2 server, false elsewhere
     */
    function isConnectedToWebSSO()
    {
        return isset($_SERVER)
            && is_array($_SERVER)
            && array_key_exists('OIDC_CLAIM_uid', $_SERVER);
    }
}

if (!function_exists('isHostedOnPlaceV2')) {
    /**
     * isHostedOnPlaceV2
     *
     * @return boolean true if this app runs on a Place v2 server, false elsewhere
     */
    function isHostedOnPlaceV2()
    {
        sendTechnicalMail(
            'Deprecated',
            "L'application " . getAppName() . " utilise encore isHostedOnPlaceV2() !",
            [SERVICE_EMAIL]
        );

        return getenv('BASE_URL') !== false;
    }
}

if (!function_exists('sendTechnicalMail')) {
    /**
     * sendTechnicalMail
     *
     * @param  string $subject L'objet du mail
     * @param  string $body Le corps du mail
     * @param  array $to (optional) Les adresses mail des destinataires, séparées par des virgules.
     * @return mixed json_decode() of called Apigile API. Should be empty.
     */
    function sendTechnicalMail($subject, $body, $to = SERVICE_EMAIL)
    {
        $body = '<!DOCTYPE html>
    <html lang="en">
    
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title></title>
    </head>
    
    <body>
    ' . nl2br($body) . '
    </body>
    
    </html>';

        $to = str_replace(";", ",", $to);
        !$to || isRuningOnDevServer()
            && $to = SERVICE_EMAIL;

        $headers = [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf8',
            'From' => 'o-reply@' . getenv("SMTP_DOMAIN"),
            'Reply-To' => SERVICE_EMAIL,
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        mail($to, $subject, $body, $headers);
    }
}

if (!function_exists('sendApiMail')) {
    /**
     * sendApiMail send an email through Apigile APIs
     *
     * @param  string $subject      L'objet du mail
     * @param  string $body         Le corps du mail
     * @param  array $recipients    Un tableau contenant les adresses mail des destinataires
     * @return mixed json_decode() of called Apigile API. Should be empty.
     */
    function sendApiMail($subject, $body, $recipients)
    {
        $apiMessage = new ApiMessage();

        return $apiMessage->sendEmail(
            'Enedis_interne',
            $recipients,
            $subject,
            $body
        );
    }
}

if (!function_exists('isRuningOnAzurDevServer')) {
    function isRuningOnAzurDevServer()
    {
        error_log('La fonction isRuningOnAzurDevServer() est dépréciée ! '
            . 'Veuillez la remplacer par isRuningOnDevServer().');

        return isRuningOnDevServer();
    }
}

if (!function_exists('isRuningOnDevServer')) {
    /**
     * Retourne true si l'application tourne sur un serveur de développement
     * @return boolean
     */
    function isRuningOnDevServer()
    {
        if (array_key_exists('FAKE_PROD', $_COOKIE)) {
            return false;
        }

        return getenv('ENVIRONMENT') === 'development'  || getenv('ENVIRONMENT') === 'preproduction' ? true : false;
    }
}

if (!function_exists('isLocalhost')) {
    /**
     * Vérifie si la requête provient de localhost.
     *
     * @return bool
     */
    function isLocalhost(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        return strpos($origin, 'localhost') !== false;
    }
}

if (!function_exists('isRuningOnProdServer')) {
    /**
     * Retourne true si l'application tourne sur un serveur de production
     * @return boolean
     */
    function isRuningOnProdServer()
    {
        return !isRuningOnDevServer();
    }
}

if (!function_exists('importFileInTable')) {
    /**
     * Importe un fichier en BDD
     * @param File $file `Le fichier csv ou XLS à importer en BDD.`
     * @param string $table `Le nom de la table. Essayez d'utiliser le format {$_tablesPrefix}_import_{$import}.`
     * @param array $options `Les options de l'import.`
     * @return null
     */
    function importFileInTable($file, $table, $options = [])
    {
        // Check if we've uploaded a file (see https://docs.php.earth/security/uploading/)
        if (empty($file)) {
            displayAlertInFront('Erreur', 'Le fichier est vide.');

            throw new CoreException('Erreur : le fichier est vide.');
        }
        if ($file['error'] !== 0) {
            displayAlertInFront('Erreur', 'Erreur dans l\'envoi du fichier.');

            throw new CoreException('Erreur dans l\'envoi du fichier.');
        }
        // Be sure we're dealing with an upload
        if (is_uploaded_file($file['tmp_name']) === false) {
            displayAlertInFront('Erreur', 'Le fichier n\'a pas été importé.');

            throw new CoreException('Error on upload: Invalid file definition', LEVEL_ERROR);
        }
        if (mime_content_type($file['tmp_name']) !== $file['type']) {
            displayAlertInFront('Erreur', 'Le contenu du fichier ne correspond pas à son extension.');

            throw new CoreException('Le contenu du fichier ne correspond pas à son extension.', LEVEL_ERROR);
        }
        $type = $file['type'];
        define('KO', 1024);
        define('MO', 1024 * 1024);
        $maxFileSize = 1 * MO;
        if (!array_key_exists('maxFileSizes', $options) && isset($options['maxFileSizes'][$table])) {
            $maxFileSize = $options['maxFileSizes'][$table];
        }
        if ($file['size'] > $maxFileSize) {
            throw new CoreException('La taille maximale de fichier est dépassée.');
        }

        // Rename the uploaded file
        $uploadName = $file['name'];
        $ext = strtolower(substr($uploadName, strripos($uploadName, '.') + 1));
        $filename = round(microtime(true)) . mt_rand() . '.' . $ext;

        $temp_dir = __DIR__ . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'temp';
        @mkdir($temp_dir);
        if (!file_exists($temp_dir . DIRECTORY_SEPARATOR . '.')) {
            $temp_dir = __DIR__ . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'temp';
            @mkdir($temp_dir);
        }
        if (!file_exists($temp_dir . DIRECTORY_SEPARATOR . '.')) {
            throw new CoreException('Impossible de créer le dossier temporaire.');
        }
        $temp_dir = realpath($temp_dir);
        $filename = $temp_dir . DIRECTORY_SEPARATOR . $filename;

        move_uploaded_file($file['tmp_name'], $filename);

        switch ($type) {
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // .xlsx
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.template': // .xltx
            case 'application/vnd.ms-excel.sheet.macroEnabled.12': // .xlsm
            case 'application/vnd.ms-excel.template.macroEnabled.12': // .xltm
                /**
                 * Conversion du fichier Excel vers CSV
                 * \Vtiful\Kernel\Excel necessite pecl="xlswriter" dans place-cloud.conf
                 */
                $excel = new \Vtiful\Kernel\Excel(['path' => '']);
                $filename = $temp_dir . DIRECTORY_SEPARATOR . 'import_' . sanitizeFileName($table) . '.csv';
                $fp = fopen($filename, 'a');
                if (!$excel->openFile($filename)->openSheet()->putCSV($fp)) {
                    throw new CoreException('Impossible de convertir le fichier Excel au format csv.');
                }
                fclose($fp);
                $encoding = mb_detect_encoding(
                    file_get_contents(
                        $file['tmp_name'],
                        false,
                        null,
                        0,
                        50000
                    ),
                    'ASCII, ISO-8859-1, ISO-8859-15, ISO-8859-9, Windows-1251, Windows-1252, UTF-8'
                );
                $options['characterSet'] = getSqlCharset($encoding);
                $options['separator']    = ',';
                $options['enclosure']    = '"';
                $options['escape']       = '\\';
                // break; // PAS DE BREAK : la suite est la même que pour le case 'text/csv'

                // no break
            case 'text/csv': // .csv
                // Ouverture du fichier csv
                ini_set('auto_detect_line_endings', true);
                $fp = fopen($filename, 'r');
                // Lecture de la première ligne (entêtes)
                !array_key_exists('characterSet', $options) && $options['characterSet'] = 'utf8';
                !array_key_exists('separator', $options) && $options['separator']    = ',';
                !array_key_exists('enclosure', $options) && $options['enclosure']    = '"';
                !array_key_exists('escape', $options) && $options['escape']       = '\\';
                $headers = fgetcsv($fp, null, $options['separator'], $options['enclosure'], $options['escape']);
                fclose($fp);
                // Création de la table vierge dans la BDD
                CoreMysqli::get()->query(generateCreateStatementFromArray($headers, $table));
                // Insertion des données dans la BDD
                loadCsvIntoTable($filename, $table, $options);

                break;

            case 'application/vnd.ms-excel': // .xls
                displayAlertInFront(
                    "Format non supporté",
                    "Le format Excel 97 n'est pas supporté. Essayez de convertir votre fichier au format Excel XLSX ou au format CSV."
                );

                break;
            default:
                throw new CoreException('Ce type de fichier n\'est pas pris en charge.');
        }
        postTreatment($table);
    }
}

if (!function_exists('getSqlCharset')) {
    function getSqlCharset($phpEncoding)
    {
        $charsetMap = array(
            'UTF-8' => 'utf8',
            'ISO-8859-15' => 'latin1',
            'ISO-8859-9' => 'latin5',
            'ISO-8859-1' => 'latin1',
            'Windows-1252' => 'cp1252',
            'Windows-1251' => 'cp1251',
            'ASCII' => 'ascii',
        );

        return isset($charsetMap[$phpEncoding]) ? $charsetMap[$phpEncoding] : 'utf8mb4';
    }
}

if (!function_exists('postTreatment')) {
    /**
     * postTreatment est définie ici a titre d"exmple.
     * Vous devez la réécrire dans votre fichier my-functions.php
     *
     * @param  mixed $table
     * @return void
     */
    function postTreatment(string $table)
    {
        global $_tablesPrefix;
        $table = CoreMysqli::get()->real_escape_string($table);
        $finalTable = str_replace('import_', '', $table);
        switch ($table) {
            case 'table1':

                /**
                 * Exemple de transformation de champs "TEXT" au format français vers "DATE" au format SQL
                 * La fonction SQL dateUS() se trouve sur les BDD de plusieurs applications de la DR Bretagne.
                 */
                $dateFields = ['date_creation', 'date_cloture'];
                $query1 = $query2 = '';
                foreach ($dateFields as $field) {
                    $query1 !== '' && $query1 .=  ', ';
                    $cleanedFiled = CoreMysqli::get()->real_escape_string($field);
                    $query1 .= "`{$cleanedFiled}` = dateUS(`{$cleanedFiled}`)";
                    $query2 .= "CHANGE `{$cleanedFiled}` `{$cleanedFiled}` DATE NULL DEFAULT NULL";
                }
                $query = "UPDATE `{$table}` SET $query1";
                '' !== $query1 && CoreMysqli::get()->query($query);
                $query = "ALTER TABLE `{$table}` {$query2};";
                '' !== $query2 && CoreMysqli::get()->query($query);

                /**
                 * Autres exemple de traitement.
                 */
                CoreMysqli::get()->query("ALTER TABLE `{$table}` CHANGE `numaff` `numaff` VARCHAR(13)");
                CoreMysqli::get()->query("ALTER TABLE `{$table}` ADD PRIMARY KEY (`numaff`);");
                CoreMysqli::get()->query("ALTER TABLE `{$table}` ADD INDEX (`date_cloture`);");
                CoreMysqli::get()->query("DELETE FROM `{$table}` WHERE `date_cloture` IS NOT NULL AND `date_cloture` < CURDATE() - INTERVAL 6 MONTH");
                /**
                 * Transfert en table d'usage.
                 */
                // Etape 1 : Je supprime la table `_backup_{$table}`
                CoreMysqli::get()->query("DROP TABLE IF EXISTS `_backup_{$table}`");
                // Etape 1 : Je renomme la table $table en _backup_$table
                CoreMysqli::get()->query("RENAME TABLE `{$finalTable}` TO `_backup_{$table}`");
                // Etape 1 : Je renomme table {$table}_provisoire en $table
                CoreMysqli::get()->query("RENAME TABLE `{$table}` TO `$finalTable`;");


                break;
            default:
                break;
        }
        CoreMysqli::get()->query("UPDATE `{$_tablesPrefix}core_file_imports` SET `lastImportDate` = NOW() WHERE `table` = '{$table}'");
    }
}
if (!function_exists('loadCsvIntoTable')) {
    /**
     * loadCsvIntoTable charge un fichier CSV dans une table de la BDD.
     *
     * @param  mixed $filename le chemin complet du fichier CSV
     * @param  mixed $table le nom de la table en BDD
     * @param  mixed $options array(separator, enclosure, escape)
     * @return mixed the result of CoreMysqli::get()->query()
     */
    function loadCsvIntoTable(string $filename, string $table, array $options = [])
    {
        // Le cas échéant, valeurs par défaut des options d'importation du CSV
        !array_key_exists('characterSet', $options) && $options['characterSet'] = 'utf8';
        !array_key_exists('separator', $options) && $options['separator']    = ',';
        !array_key_exists('enclosure', $options) && $options['enclosure']    = '"';
        !array_key_exists('escape', $options) && $options['escape']       = '\\';

        return CoreMysqli::get()->query(
            "LOAD DATA LOCAL INFILE
                '" . CoreMysqli::get()->real_escape_string($filename) . "'
            INTO TABLE
                `" . CoreMysqli::get()->real_escape_string($table) . "`
            CHARACTER SET
                {$options['characterSet']}
            FIELDS
                TERMINATED BY '{$options['separator']}'
                ENCLOSED BY '{$options['enclosure']}'
                ESCAPED BY '{$options['escape']}'
            IGNORE 1 LINES"
        );
    }
}

if (!function_exists('sanitizeFileName')) {
    /**
     * sanitizeFileName
     *
     * @param  string $filename the filename to clean
     * @return string the cleaned filename
     */
    function sanitizeFileName(string $filename): string
    {
        $dangerous_characters = array(" ", '"', "'", "&", "/", "\\", "?", "#", ">", "<", "|");
        $safe_filename = str_replace($dangerous_characters, '_', $filename);
        $safe_filename = remove_accents($safe_filename);
        $safe_filename = preg_replace('/[^A-Za-z0-9-_\.[:blank:]]/', '', $safe_filename);
        $safe_filename = preg_replace('/[[:blank:]]+/', '_', $safe_filename);
        $safe_filename = trim($safe_filename, '_-');

        // Ensure unique filename if needed
        $info = pathinfo($safe_filename);
        $baseName = $info['filename'];
        $extension = $info['extension'];
        if (file_exists($safe_filename)) {
            $safe_filename = $baseName . '_' . time() . '.' . $extension;
        }

        return $safe_filename;
    }
}

if (!function_exists('remove_accents')) {
    /**
     * remove_accents will remove accents from text
     *
     * @param  string $string the entry
     * @return string the string without accent and specials characters
     */
    function remove_accents(string $string): string
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if (function_exists('iconv')) {
            $string = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $string);
        }

        $chars = array(
            'ª' => 'a',
            'º' => 'o',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 's',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'Ā' => 'A',
            'ā' => 'a',
            'Ă' => 'A',
            'ă' => 'a',
            'Ą' => 'A',
            'ą' => 'a',
            'Ć' => 'C',
            'ć' => 'c',
            'Ĉ' => 'C',
            'ĉ' => 'c',
            'Ċ' => 'C',
            'ċ' => 'c',
            'Č' => 'C',
            'č' => 'c',
            'Ď' => 'D',
            'ď' => 'd',
            'Đ' => 'D',
            'đ' => 'd',
            'Ē' => 'E',
            'ē' => 'e',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ė' => 'E',
            'ė' => 'e',
            'Ę' => 'E',
            'ę' => 'e',
            'Ě' => 'E',
            'ě' => 'e',
            'Ĝ' => 'G',
            'ĝ' => 'g',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ġ' => 'G',
            'ġ' => 'g',
            'Ģ' => 'G',
            'ģ' => 'g',
            'Ĥ' => 'H',
            'ĥ' => 'h',
            'Ħ' => 'H',
            'ħ' => 'h',
            'Ĩ' => 'I',
            'ĩ' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Į' => 'I',
            'į' => 'i',
            'İ' => 'I',
            'ı' => 'i',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'ĸ' => 'k',
            'Ĺ' => 'L',
            'ĺ' => 'l',
            'Ļ' => 'L',
            'ļ' => 'l',
            'Ľ' => 'L',
            'ľ' => 'l',
            'Ŀ' => 'L',
            'ŀ' => 'l',
            'Ł' => 'L',
            'ł' => 'l',
            'Ń' => 'N',
            'ń' => 'n',
            'Ņ' => 'N',
            'ņ' => 'n',
            'Ň' => 'N',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ŋ' => 'N',
            'ŋ' => 'n',
            'Ō' => 'O',
            'ō' => 'o',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ő' => 'O',
            'ő' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Ŗ' => 'R',
            'ŗ' => 'r',
            'Ř' => 'R',
            'ř' => 'r',
            'Ś' => 'S',
            'ś' => 's',
            'Ŝ' => 'S',
            'ŝ' => 's',
            'Ş' => 'S',
            'ş' => 's',
            'Š' => 'S',
            'š' => 's',
            'Ţ' => 'T',
            'ţ' => 't',
            'Ť' => 'T',
            'ť' => 't',
            'Ŧ' => 'T',
            'ŧ' => 't',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ů' => 'U',
            'ů' => 'u',
            'Ű' => 'U',
            'ű' => 'u',
            'Ų' => 'U',
            'ų' => 'u',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ŷ' => 'Y',
            'ŷ' => 'y',
            'Ÿ' => 'Y',
            'Ź' => 'Z',
            'ź' => 'z',
            'Ż' => 'Z',
            'ż' => 'z',
            'Ž' => 'Z',
            'ž' => 'z',
            'ſ' => 's',
            '€' => 'E',
            '£' => 'L',
            '©' => '(c)',
            '®' => '(r)',
            '™' => '(tm)',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Š' => 'S',
            'š' => 's',
            'Ž' => 'Z',
            'ž' => 'z',
            'Å' => 'A',
            'å' => 'a',
            'Æ' => 'AE',
            'æ' => 'ae',
            'Ø' => 'O',
            'ø' => 'o',
            'Þ' => 'TH',
            'þ' => 'th',
            'Ð' => 'D',
            'ð' => 'd',
            'Ÿ' => 'Y',
            'ÿ' => 'y',
            'Â' => 'A',
            'â' => 'a',
            'Ê' => 'E',
            'ê' => 'e',
            'Î' => 'I',
            'î' => 'i',
            'Ô' => 'O',
            'ô' => 'o',
            'Û' => 'U',
            'û' => 'u',
            'Ç' => 'C',
            'ç' => 'c',
            'Ñ' => 'N',
            'ñ' => 'n',
            'Ã' => 'A',
            'ã' => 'a',
            'Õ' => 'O',
            'õ' => 'o',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ä' => 'A',
            'ä' => 'a',
            'Ë' => 'E',
            'ë' => 'e',
            'Ï' => 'I',
            'ï' => 'i',
            'Ö' => 'O',
            'ö' => 'o',
            'Ü' => 'U',
            'ü' => 'u',
            'À' => 'A',
            'à' => 'a',
            'È' => 'E',
            'è' => 'e',
            'Ì' => 'I',
            'ì' => 'i',
            'Ò' => 'O',
            'ò' => 'o',
            'Ù' => 'U',
            'ù' => 'u',
            'Á' => 'A',
            'á' => 'a',
            'É' => 'E',
            'é' => 'e',
            'Í' => 'I',
            'í' => 'i',
            'Ó' => 'O',
            'ó' => 'o',
            'Ú' => 'U',
            'ú' => 'u',
            'Ý' => 'Y',
            'ý' => 'y',
            'Ă' => 'A',
            'ă' => 'a',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ā' => 'A',
            'ā' => 'a',
            'Ē' => 'E',
            'ē' => 'e',
            'Ī' => 'I',
            'ī' => 'i',
            'Ō' => 'O',
            'ō' => 'o',
            'Ū' => 'U',
            'ū' => 'u'
        );

        return strtr($string, $chars);
    }
}

if (!function_exists('generateCreateStatementFromArray')) {
    function generateCreateStatementFromArray(array $array, string $table)
    {

        $query = "CREATE TABLE IF NOT EXISTS `" . CoreMysqli::get()->real_escape_string($table) . "` (\n";
        $query .= "\n    `" . implode("` TEXT NULL DEFAULT NULL,\n    `", $array) . "` TEXT NULL DEFAULT NULL,\n";
        $query .= "\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        return $query;
    }
}

if (!function_exists('logDCPtreatment')) {
    /**
     * logDCPtreatment() envoie au backoffice RGPD le nombre de lignes supprimmées et/ou anonymisées
     * @param string[]|string $robot `Le nom du robot (généralement, celui de l'application).`
     * @param int $numberOfErasedRows `Le nombre de lignes supprimées. Mettre 0l s'il ne s'agit que d'anonymisations.`
     * @param int $numberOfAnonymizedRows `Le nombre de lignes anonymisées.
     * Facultatif s'il ne s'agit que de suppressions.`
     * @return bool false si en dev / true si tout s'est bien déroulé
     */
    function logDCPtreatment($robot, $numberOfErasedRows, $numberOfAnonymizedRows = null)
    {
        if (isRuningOnDevServer()) {
            return;
        }
        $params = [
            "robot" => $robot,
            "url" => getenv('BASE_URL') . '/#'
        ];
        $numberOfErasedRows && $params['nb_suppressions'] = $numberOfErasedRows;
        $numberOfAnonymizedRows && $params['nb_anonymisations'] = $numberOfAnonymizedRows;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://appli-stats.place-cloud-enedis.fr/API/log-purges.php");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($ch);
        if (false === $response) {
            throw new CoreException("logDCPtreatment() : erreur curl");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode != 201) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($response, $headerSize);

            throw new CoreException("logDCPtreatment() : erreur $httpCode -> " . $body);
        }
        curl_close($ch);
    }
}

if (!function_exists('simpleHttpGet')) {
    /**
     * Retourne le contenu d'une ressource Web.
     * @param string $url `L'adresse de la ressource à aspirer`
     * @return string `Le contenu de la page aspirée`
     */
    function simpleHttpGet($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        return curl_exec($ch);
    }
}

if (!function_exists('updateParametersIfNeeded')) {
    /**
     * Cette fonction permet de migrer les tables core_users et core_roles au gré des évolutions du squelette
     * A savoir : la table {$_tablesPrefix}core_users ne doit pas être utilisée pour stocker d'autres informations,
     * car idéalement elle ne devrait plus exister pour être remplacée par Gardian-IDM.
     *
     * @return null
     */
    function updateParametersIfNeeded()
    {
        $appParameters = new CoreParameters();
        if (!$appParameters->parameterExists('gitlab_zac_api_url')) {
            if ($appParameters->parameterExists('gitlab_zac_server')) {
                $appParameters->setParameter(
                    'gitlab_zac_api_url',
                    $appParameters->getParameter('gitlab_zac_server'),
                    'Url de base de l\'API du serveur du Git de déploiement (ZAC).'
                        . 'Par exemple : https://gitlab.zac.web-enedis.fr/api/v4'
                );
                $appParameters->deleteParameter('gitlab_zac_server');
            } else {
                $appParameters->getParameter(
                    'gitlab_zac_api_url',
                    'Url de base de l\'API du serveur du Git de déploiement (ZAC).'
                        . 'Par exemple : https://gitlab.zac.web-enedis.fr/api/v4'
                );
            }
        }
        $appParameters->getParameter(
            'gitlab_zac_token',
            'Token valide permettant l\'accès aux API du Git de déploiement',
            true
        );
        $appParameters->getParameter(
            'gitlab_zac_group_id',
            'id du groupe de la DR dans le Git de déploiement, visible dans l\'URL. '
                . 'Par exemple : 93788'
        );
        $appParameters->getParameter(
            'gitlab_zac_project_id',
            'id du projet sur le Git de déploiement, visible dans l\'URL. '
                . 'Par exemple : 1024'
        );
        $appParameters->getParameter(
            'gitlab_source_api_url',
            'Url de base de l\'API du serveur du Git de dépôt de code source. '
                . 'Par exemple : https://gitlab.adaje.oi.enedis.fr/api/v4'
        );
        $appParameters->getParameter(
            'gitlab_source_token',
            'Token valide permettant l\'accès aux API du Git de dépôt de code source',
            true
        );
        $appParameters->getParameter(
            'gitlab_source_group_id',
            'id du groupe de la DR dans le Git de dépôt de code source, visible dans l\'URL. '
                . 'Par exemple : 99720'
        );
        $appParameters->getParameter(
            'gitlab_source_project_id',
            'id du projet sur le Git de dépôt de code source, visible dans l\'URL. '
                . 'Par exemple : 32768'
        );
    }
}
if (!function_exists('purgeObsoleteUsers')) {
    /**
     * Cette fonction permet de migrer les tables core_users et core_roles au gré des évolutions du squelette
     * A savoir : la table {$_tablesPrefix}core_users ne doit pas être utilisée pour stocker d'autres informations,
     * car idéalement elle ne devrait plus exister pour être remplacée par Gardian-IDM.
     *
     * @return null
     */
    function purgeObsoleteUsers()
    {
        global $_tablesPrefix;
        CoreMysqli::get()->autocommit(false);

        try {
            CoreMysqli::get()->query(
                "DELETE FROM
                    `{$_tablesPrefix}core_users`
                WHERE
                    ( `creation_date` < CURDATE() - INTERVAL 6 MONTH AND `last_successfull_login` IS NULL)
                    OR
                    ( `last_successfull_login` < CURDATE() - INTERVAL 13 MONTH );"
            );
            CoreMysqli::get()->commit();
        } catch (Exception $e) {
            CoreMysqli::get()->rollback();
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
        CoreMysqli::get()->autocommit(true);
    }
}

if (!function_exists('updateDatabaseIfNeeded')) {
    /**
     * Cette fonction permet de migrer les tables core_users et core_roles au gré des évolutions du squelette
     * A savoir : la table {$_tablesPrefix}core_users ne doit pas être utilisée pour stocker d'autres informations,
     * car idéalement elle ne devrait plus exister pour être remplacée par Gardian-IDM.
     *
     * @return null
     */
    function updateDatabaseIfNeeded()
    {
        try {
            createCoreUsersTableIfNotExists();
            createCoreRolesTableIfNotExists();
            fixPerimetersInCoreRolesTable();
            createParametersTableIfNotExists();
            updateParametersTableIfNeeded();
            createLogApiTableIfNotExists();
            updateLogApiTableIfNeeded();
            createTablesToExportTableIfNotExists();
            createSubsitutionsTableIfNotExists();
            createFileImportsTableIfNotExists();
            createCacheTableIfNotExists();
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
    }
}

if (!function_exists('existsTable')) {
    /**
     * existsTable returns true is the table exists, false elsewhere.
     *
     * @param  string $table
     * @return boolean
     */
    function existsTable($table)
    {
        $tableType = false;
        $query = "SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_NAME = '$table'";
        $result = CoreMysqli::get()->query($query);
        if ($row = $result->fetch_object()) {
            $tableType = $row->TABLE_TYPE;
        }

        return ($tableType === 'BASE TABLE') ? true : false;
    }
}

if (!function_exists('createCoreUsersTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}auth_users si elle n'existe pas.
     * Si elle existe sous le nom {$_tablesPrefix}users, on la renomme en {$_tablesPrefix}auth_users.
     *
     * A savoir : cette table ne doit pas être utilisée pour stocker d'autres informations,
     * car idéalement elle ne devrait plus exister pour être remplacée par Gardian-IDM.
     * @return void
     */
    function createCoreUsersTableIfNotExists()
    {
        global $_tablesPrefix;
        if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}core_users")) {
            if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}auth_users")) {
                if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}users")) {
                    $query =
                        "CREATE TABLE IF NOT EXISTS `{$_tablesPrefix}core_users` (
                        `nni` varchar(8) NOT NULL,
                        `firstname` varchar(40) NOT NULL,
                        `lastname` varchar(40) NOT NULL,
                        `email` varchar(90) DEFAULT NULL,
                        `roles` text NOT NULL,
                        `creation_date` date NOT NULL,
                        `last_successfull_login` date DEFAULT NULL,
                        PRIMARY KEY (`nni`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                    CoreMysqli::get()->query($query);
                } else {
                    CoreMysqli::get()->query("RENAME TABLE `{$_tablesPrefix}users` TO `{$_tablesPrefix}core_users`");
                }
            } else {
                CoreMysqli::get()->query("RENAME TABLE `{$_tablesPrefix}auth_users` TO `{$_tablesPrefix}core_users`");
            }
        }
    }
}

if (!function_exists('createCoreRolesTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}auth_roles si elle n'existe pas.
     * Si elle existe sous le nom {$_tablesPrefix}roles, on la renomme en {$_tablesPrefix}auth_roles.
     *
     * A savoir : cette table ne doit pas être utilisée pour stocker d'autres informations,
     * car idéalement elle ne devrait plus exister pour être remplacée par Gardian-IDM.
     * @return void
     */
    function createCoreRolesTableIfNotExists()
    {
        global $_tablesPrefix;
        if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}core_roles")) {
            if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}auth_roles")) {
                if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}roles")) {
                    $query =
                        "CREATE TABLE IF NOT EXISTS `{$_tablesPrefix}core_roles` (
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `label` varchar(40) NOT NULL,
                        `description` varchar(255) NOT NULL,
                        `order` int(10) unsigned NOT NULL,
                        PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                    CoreMysqli::get()->query($query);
                } else {
                    $query = "RENAME TABLE `{$_tablesPrefix}roles` TO `{$_tablesPrefix}core_roles`";
                    CoreMysqli::get()->query($query);
                }
            } else {
                $query = "RENAME TABLE `{$_tablesPrefix}auth_roles` TO `{$_tablesPrefix}core_roles`";
                CoreMysqli::get()->query($query);
            }
        }
    }
}
if (!function_exists('fixPerimetersInCoreRolesTable')) {
    /**
     * Les roles ne doivent plus contenir _ENEDIS
     * Les roles des utilisateurs sont aussi modifiés pour intégrer _1464 (DR Bretagne) au lieu de _ENEDIS
     * @return void
     */
    function fixPerimetersInCoreRolesTable()
    {
        global $_tablesPrefix;
        $result = CoreMysqli::get()->query("SELECT 1 FROM `{$_tablesPrefix}core_roles` WHERE `label` LIKE '%_ENEDIS'");
        if ($result->num_rows > 0) {
            CoreMysqli::get()->query(
                "UPDATE `{$_tablesPrefix}core_roles` SET `label`= REPLACE(`label`, '_ENEDIS', '');"
            );
            CoreMysqli::get()->query(
                "UPDATE `{$_tablesPrefix}core_users` SET `roles`= REPLACE(`roles`, '_ENEDIS', '_1464');"
            );
        }
    }
}
if (!function_exists('createParametersTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}core_parameters si elle n'existe pas.
     * @return void
     */
    function createParametersTableIfNotExists()
    {
        global $_tablesPrefix;
        $query = "CREATE TABLE IF NOT EXISTS
            `{$_tablesPrefix}core_parameters`
            (
                `name` varchar(255) NOT NULL,
                `value` varchar(255) NOT NULL,
                `type` varchar(255) NOT NULL,
                `description` text NOT NULL,
                PRIMARY KEY (`name`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ";
        CoreMysqli::get()->query($query);
    }
}

if (!function_exists('updateParametersTableIfNeeded')) {
    /**
     * Création de la table {$_tablesPrefix}core_parameters si elle n'existe pas.
     * @return void
     */
    function updateParametersTableIfNeeded()
    {
        global $_tablesPrefix;
        $table = CoreMysqli::get()->real_escape_string("{$_tablesPrefix}core_parameters");
        $column = "hidden";

        $query = "SHOW COLUMNS FROM `$table` LIKE '$column';";
        $result = CoreMysqli::get()->query($query);
        if ($result && $result->num_rows === 0) {
            $query = "ALTER TABLE `$table` ADD $column BOOLEAN NOT NULL DEFAULT FALSE;";
            $result = CoreMysqli::get()->query($query);
            if (false === $result) {
                die("Erreur sql : $query");
            }
        }
    }
}

if (!function_exists('createLogApiTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}core_log_api si elle n'existe pas.
     * @return void
     */
    function createLogApiTableIfNotExists()
    {
        global $_tablesPrefix;
        $query = "CREATE TABLE IF NOT EXISTS
        `{$_tablesPrefix}core_log_api`
        (
            `subject` varchar(255) NOT NULL,
            `date` date NOT NULL,
            `count` int(5) NOT NULL,
            `error_count` int(5) NOT NULL,
            PRIMARY KEY (`subject`,`date`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        CoreMysqli::get()->query($query);
    }
}

if (!function_exists('updateLogApiTableIfNeeded')) {
    /**
     * updateLogApiTableIfNeeded
     * @return void
     */
    function updateLogApiTableIfNeeded()
    {
        global $_tablesPrefix;
        $table = CoreMysqli::get()->real_escape_string("{$_tablesPrefix}core_log_api");
        $column = "error_count";

        $query = "SHOW COLUMNS FROM `$table` LIKE '$column';";
        $result = CoreMysqli::get()->query($query);
        if (false === $result) {
            die("Erreur sql : $query");
        }

        if ($result->num_rows === 0) {
            $query = "ALTER TABLE `$table` ADD $column INT(5) NOT NULL AFTER `count`;";
            $result = CoreMysqli::get()->query($query);
            if (false === $result) {
                die("Erreur sql : $query");
            }
        }
    }
}

if (!function_exists('createTablesToExportTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}core_log_api si elle n'existe pas.
     * @return void
     */
    function createTablesToExportTableIfNotExists()
    {
        global $_tablesPrefix;
        $query =
            "CREATE TABLE IF NOT EXISTS `{$_tablesPrefix}core_tables_to_export` (
                `name` varchar(120) NOT NULL,
                `checked` tinyint(1) NOT NULL DEFAULT '0'
            )";
        CoreMysqli::get()->query($query);
        $query =
            "CREATE TABLE IF NOT EXISTS `{$_tablesPrefix}core_fields_anonymization` (
                `id` int NOT NULL,
                `table_name` varchar(255) DEFAULT NULL,
                `field_name` varchar(255) DEFAULT NULL,
                `anonymize` tinyint(1) DEFAULT '0',
                `anonymizationMethod` varchar(255) DEFAULT NULL,
                `anonymizationConfig` json DEFAULT NULL
            )";
        CoreMysqli::get()->query($query);
    }
}
if (!function_exists('createFileImportsTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}core_log_api si elle n'existe pas.
     * @return void
     */
    function createFileImportsTableIfNotExists()
    {
        global $_tablesPrefix;
        $query =
            "CREATE TABLE IF NOT EXISTS `{$_tablesPrefix}core_file_imports` (
            `table` varchar(80) NOT NULL,
            `lastImportDate` datetime NOT NULL,
            PRIMARY KEY (`table`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
        CoreMysqli::get()->query($query);
    }
}

if (!function_exists('createCacheTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}core_log_api si elle n'existe pas.
     * @return void
     */
    function createCacheTableIfNotExists()
    {
        global $_tablesPrefix;
        $query =
            "CREATE TABLE IF NOT EXISTS `{$_tablesPrefix}core_cache` (
                `access_token` varchar(80) NOT NULL,
                `uid` text NOT NULL,
                `mail` text NOT NULL,
                `sn` text NOT NULL,
                `givenName` text NOT NULL DEFAULT '',
                `departmentNumber` text NOT NULL DEFAULT '',
                `gardianHierarchie` text NOT NULL DEFAULT '',
                `gardianSesameProfil` text NOT NULL DEFAULT '',
                `grants` text NOT NULL DEFAULT '',
                `date` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
        CoreMysqli::get()->query($query);
    }
}

if (!function_exists('createSubsitutionsTableIfNotExists')) {
    /**
     * Création de la table {$_tablesPrefix}core_log_api si elle n'existe pas.
     * @return void
     */
    function createSubsitutionsTableIfNotExists()
    {
        global $_tablesPrefix;
        if (!CoreMysqli::get()->tableExists("{$_tablesPrefix}core_substitutions")) {
            $query =
                "CREATE TABLE `{$_tablesPrefix}core_substitutions` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(10) NOT NULL,
            value VARCHAR(50) NOT NULL
            );";
            CoreMysqli::get()->query($query);
            $query =
                "INSERT INTO `{$_tablesPrefix}core_substitutions` (type, value) VALUES
                ('address', '7, allée des Trois Licornes à Grandes Langues'),
                ('address', '12, rue des Lutins aux Pieds d\'Argent'),
                ('address', '3, impasse du Chaudron Bouillonnant et Chantant'),
                ('address', '15, boulevard des Nuages Dansant la Polka'),
                ('address', '22, chemin des Champignons Lumineux à Pois'),
                ('address', '9, rue de l\'Étoile Filante Hyperactive'),
                ('address', '18, avenue des Arbres Chuchoteurs de Ragots'),
                ('address', '5, place du Dragon Éternueur de Feu'),
                ('address', '14, rue de la Baguette Magique Chatouilleusse'),
                ('address', '2, impasse du Miroir Moqueur et Grimaçant'),
                ('address', '11, allée des Papillons Jongleurs de Pollen'),
                ('address', '20, rue de la Poussière de Fée Éternueuse'),
                ('address', '6, place du Phénix Amateur de Barbecue'),
                ('address', '13, boulevard du Sablier Inversé et Rebelle'),
                ('address', '8, rue de la Lanterne Flottante et Bavarde'),
                ('address', '17, avenue du Portail Mystique à Sens Unique'),
                ('address', '4, impasse du Chêne Millénaire Insomniaque'),
                ('address', '19, rue de la Plume de Phénix Chatouilleuse'),
                ('address', '1, place de la Fontaine Chantante Fausse Note'),
                ('address', '16, chemin des Pierres de Lune Sauteuses'),
                ('address', '10, rue de l\'Horloge Éternelle en Retard'),
                ('address', '23, avenue des Fleurs Parlantes et Bavardes'),
                ('address', '5, impasse du Miroir Magique Bigleux'),
                ('address', '21, rue du Chaudron Fumant et Toussotant'),
                ('address', '8, place de l\'Échiquier Géant Dansant'),
                ('address', '27, boulevard des Sirènes Chanteuses de Rock'),
                ('address', '33, rue des Gnomes Bodybuilders'),
                ('address', '42, avenue des Licornes Breakdanceuses'),
                ('address', '6, impasse des Elfes Rappeurs'),
                ('address', '13, rue du Troll Parfumeur'),
                ('address', '25, allée des Fées Catcheuses'),
                ('address', '9, place du Centaure Patineur Artistique'),
                ('address', '31, rue de la Sorcière Influenceuse'),
                ('address', '4, chemin du Gobelin Barista Hipster'),
                ('address', '19, avenue du Griffon Philosophe'),
                ('address', '7, impasse du Farfadet Culturiste'),
                ('address', '28, rue de la Mandragore Mélomane'),
                ('address', '11, place du Kraken Danseur de Flamenco'),
                ('address', '36, allée du Basilic Végétarien'),
                ('address', '2, rue de la Gorgone Coiffeuse'),
                ('address', '17, boulevard du Leprechaun Rappeur'),
                ('address', '39, avenue de la Vouivre Youtubeuse'),
                ('address', '8, impasse du Cerbère Gastronome'),
                ('address', '24, rue du Vampire Allergique au Soleil'),
                ('address', '14, place de la Banshee Professeur de Chant'),
                ('address', '30, allée du Yéti Couturier de Haute Coiffure'),
                ('address', '1, rue du Phénix Pyrophobe'),
                ('address', '20, avenue du Minotaure Danseur de Ballet'),
                ('address', '35, impasse de la Harpie Décoratrice d\'Intérieur'),
                ('address', '12, boulevard du Cyclope Opticien'),
                ('firstname', 'Marie'), ('firstname', 'Jean'), ('firstname', 'Sophie'), ('firstname', 'Pierre'),
                ('firstname', 'François'), ('firstname', 'Isabelle'), ('firstname', 'Nicolas'), ('firstname', 'Claire'),
                ('firstname', 'Thomas'), ('firstname', 'Élodie'), ('firstname', 'Antoine'), ('firstname', 'Chloé'),
                ('firstname', 'Julien'), ('firstname', 'Aurélie'), ('firstname', 'Sébastien'), ('firstname', 'Céline'),
                ('firstname', 'Émilie'), ('firstname', 'Mathieu'), ('firstname', 'Nathalie'), ('firstname', 'Olivier'),
                ('firstname', 'Julie'), ('firstname', 'Sylvie'), ('firstname', 'Vincent'), ('firstname', 'Laurence'),
                ('firstname', 'Philippe'), ('firstname', 'Caroline'), ('firstname', 'David'), ('firstname', 'Sandrine'),
                ('firstname', 'Valérie'), ('firstname', 'Pascal'), ('firstname', 'Stéphanie'), ('firstname', 'Christophe'),
                ('firstname', 'Audrey'), ('firstname', 'Patrick'), ('firstname', 'Delphine'), ('firstname', 'Jérôme'),
                ('firstname', 'Mélanie'), ('firstname', 'Thierry'), ('firstname', 'Laure'), ('firstname', 'Anne'),
                ('firstname', 'Guillaume'), ('firstname', 'Florence'), ('firstname', 'Éric'), ('firstname', 'Virginie'),
                ('firstname', 'Laurent'), ('firstname', 'Hélène'), ('firstname', 'Gilles'), ('firstname', 'Martine'),
                ('firstname', 'Bernard'), ('firstname', 'Catherine'),
                ('lastname', 'LUCIOLE-DANSANTE'),
                ('lastname', 'CHAUDRON-RIEUR'),
                ('lastname', 'BAGUETTE-SIFFLANTE'),
                ('lastname', 'FARFADET-VOLANT'),
                ('lastname', 'LICORNE-PAILLETEE'),
                ('lastname', 'ELFE-BREAKDANSEUR'),
                ('lastname', 'FEE-ROCKEUSE'),
                ('lastname', 'GOBELIN-CHATOUILLEUR'),
                ('lastname', 'TROLL-PARFUME'),
                ('lastname', 'PHENIX-GLOUTON'),
                ('lastname', 'SIRENE-ROLLEUSE'),
                ('lastname', 'DRAGON-CALIN'),
                ('lastname', 'CENTAURE-DISCO'),
                ('lastname', 'GNOME-ACROBATE'),
                ('lastname', 'GRIFFON-POETE'),
                ('lastname', 'NYMPHE-BOXEUSE'),
                ('lastname', 'OGRE-VEGETARIEN'),
                ('lastname', 'PIXIE-MECANICIENNE'),
                ('lastname', 'CYCLOPE-COUTURIER'),
                ('lastname', 'BANSHEE-MURMURANTE'),
                ('lastname', 'MINOTAURE-DANSEUR'),
                ('lastname', 'LEPRECHAUN-RAPPEUR'),
                ('lastname', 'HARPIE-CHANTEUSE'),
                ('lastname', 'MANTICORE-TRICOTEUSE'),
                ('lastname', 'BASILIC-JONGLEUR'),
                ('lastname', 'CHIMERE-CUISINIERE'),
                ('lastname', 'CERBERE-FLEURISTE'),
                ('lastname', 'KRAKEN-MASSEUR'),
                ('lastname', 'GORGONE-COIFFEUSE'),
                ('lastname', 'HIPPOGRIFFE-PATINEUR'),
                ('lastname', 'SPHINX-COMIQUE'),
                ('lastname', 'YETI-SURFEUR'),
                ('lastname', 'DRYADE-POMPIERE'),
                ('lastname', 'SATYRE-MAGICIEN'),
                ('lastname', 'SYLPHE-BODYBUILDER'),
                ('lastname', 'KORRIGAN-ASTRONAUTE'),
                ('lastname', 'DJINN-PATISSIER'),
                ('lastname', 'VAMPIRE-BRONZE'),
                ('lastname', 'LOUP-GAROU-VEGETALIEN'),
                ('lastname', 'FANTOME-INFLUENCEUR'),
                ('lastname', 'SORCIERE-YOUTUBEUSE'),
                ('lastname', 'GEANT-MINIATURE'),
                ('lastname', 'NAIN-ECHASSIER'),
                ('lastname', 'MUSE-RAPPEUSE'),
                ('lastname', 'PEGASE-BREAKDANSEUR'),
                ('lastname', 'GREMLIN-PHILOSOPHE'),
                ('lastname', 'TITAN-BALLERINE'),
                ('lastname', 'MEDUSE-OPTICIENNE'),
                ('lastname', 'HYDRE-COIFFEUSE'),
                ('lastname', 'GRIFFON-BLOGUEUR');";
            CoreMysqli::get()->query($query);
        }
    }
}

if (!function_exists('logWithMemoryUsage')) {
    /**
     * Ajoute une ligne dans system_log contenant l'empreinte mémoire, le nom du fichier et le texte.
     * @param string $fichier `Le nom du fichier qui appelle cette, doit être __FILE__`
     * @param string $texte `Le texte à afficher`
     */
    function logWithMemoryUsage($fichier, $texte)
    {
        $message = sprintf("%'.09d", memory_get_usage())
            . ' : '
            . basename($fichier) . ' : '
            . removeAccentFromText($texte);
        error_log($message);
        unset($message);
    }
}

if (!function_exists('displayAlertInFront')) {
    /**
     * Cette fonction affiche une alerte côté front et stoppe l'exécution de l'API.
     * @param string $title `Le titre affiché sur le Toastr`
     * @param string $message `Le texte à afficher dans le Toastr`
     */
    function displayAlertInFront($title, $message)
    {
        global $tempArray;
        $tempArray['alertToDisplay'] = array(
            'type' => 'danger',
            'title' => $title,
            'message' => $message
        );
        !headers_sent() && header('Content-Type: application/json');
        die(json_encode($tempArray));
    }
}

if (!function_exists('displaySuccessInFront')) {
    /**
     * Cette fonction affiche une alerte côté front et stoppe l'exécution de l'API.
     * @param string $title `Le titre affiché sur le Toastr`
     * @param string $message `Le texte à afficher dans le Toastr`
     */
    function displaySuccessInFront($title, $message)
    {
        global $tempArray, $JSONoutput;
        null === $tempArray && $tempArray = array();
        $tempArray['alertToDisplay'] = array(
            'type' => 'success',
            'title' => $title,
            'message' => $message
        );
        $JSONoutput = json_encode($tempArray);
    }
}

if (!function_exists('abortIfNotGranted')) {
    /**
     * Si l'utilisateur ne possède pas à minima UN des droits nécessaires,
     * cette fonction stoppe l'exécution de l'API et retorne un code d'erreur 403.
     * Si $field est renseigné, un morceau de clause WHERE permettant de filtrer
     * selon le périmètre d'habilitation de l'utilisateur sera créé via la globale $perimeterFilter.
     * @param string[]|string $rights `Le (ou les) droit(s) nécessaire(s) pour continuer.`
     * @param string $field Le nom du champ en base de données, qui contient le périmètre. Par exemple `agent`.`fsdum`.
     * @return null
     */
    function abortIfNotGranted($rights, $field = false)
    {
        global $_USER;

        if (!isset($_USER['nni'])) {
            !headers_sent() && @header('Temporary-Header: True', true, 401);
            header_remove('Temporary-Header');
            http_response_code(401);
            die();
        }
        if (!isGranted($rights)) {
            !headers_sent() && @header('Temporary-Header: True', true, 403);
            header_remove('Temporary-Header');
            http_response_code(403);
            die();
        }
        if ($field) {
            createPerimeterFilterForGrant($rights, $field);
        }
    }
}

if (!function_exists('isGranted')) {
    /**
     * Vérifie si l'utilisateur possède au moins un des droits nécessaires.
     *
     * @param string|string[] $rights Le(s) droit(s) nécessaire(s) pour continuer.
     * @return bool Retourne true si l'utilisateur a au moins un des droits, false sinon.
     */
    function isGranted(mixed $rights): bool
    {
        global $_USER;
        !is_array($rights) && $rights = [$rights];

        return isset($_USER['nni']) && !empty(array_intersect($rights, $_USER['grants'] ?? []));
    }
}

if (!function_exists('hasRole')) {
    /**
     * Si l'utilisateur ne possède pas à minima UN des droits nécessaires,
     * cette fonction stoppe l'exécution de l'API et retorne un code d'erreur 403.
     * Si $field est renseigné, un morceau de clause WHERE permettant de filtrer
     * selon le périmètre d'habilitation de l'utilisateur sera créé via la globale $perimeterFilter.
     * @param string[]|string $rights `Le (ou les) droit(s) nécessaire(s) pour continuer.`
     * @param string $field Le nom du champ en base de données, qui contient le périmètre. Par exemple `agent`.`fsdum`.
     * @return null
     */
    function hasRole($role)
    {
        global $_tablesPrefix, $_nni, $_USER;
        $stmt = CoreMysqli::get()->prepare("SELECT `roles` FROM `{$_tablesPrefix}core_users` WHERE `nni` LIKE ?");
        $stmt->bind_param('s', $_nni);
        $stmt->execute();
        $roles = '';
        $stmt->bind_result($roles);
        if ($stmt->fetch()) {
            $myRoles = @explode(',', $roles);
            isset($_USER['gardianSesameProfil'])
                && is_array($_USER['gardianSesameProfil'])
                && is_array($myRoles)
                && $myRoles = array_merge($myRoles, $_USER['gardianSesameProfil']);
            array_key_exists('OIDC_CLAIM_gardianSesameProfil', $_SERVER)
                && is_array($_SERVER['OIDC_CLAIM_gardianSesameProfil'])
                && is_array($myRoles)
                && $myRoles = array_merge($myRoles, $_USER['gardianSesameProfil']);
            /**
             * Test si développeur en mode test de profil
             */
            foreach ($myRoles as $role) {
                if (strpos($role, '_' . $role . '_') !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('createPerimeterFilterForGrant')) {
    /**
     * Cette fonction créé un morceau de clause WHERE permettant de filtrer
     * selon le périmètre d'habilitation de l'utilisateur.
     * @param string[]|string $rights `Le (ou les) droit(s) dont on droit extraire le périmètre d'habilitation.`
     * @param string $field Le nom du champ en base de données, qui contient le périmètre. Par exemple `agent`.`fsdum`.
     * @return null
     * <code>
     * createPerimeterFilterForGrant("GRAPHIQUES", "`agents`.`sdum`");
     * </code>
     * @author Olivier Mansencal <olivier.mansencal@enedis.fr>
     * @copyright Copyright (c) 2021, Olivier Mansencal
     */
    function createPerimeterFilterForGrant($rights, $field)
    {
        global $perimeterFilter, $_myGrants;

        !is_array($rights) && $rights = array($rights);

        if (strpos($field, '`') === false) {
            $field = "`$field`";
        }

        $tempPerimeterFilter = '';
        foreach ($_myGrants as $_myGrant) {
            $parts = explode('_', $_myGrant);
            $role = $parts[1];
            if (in_array($role, $rights)) {
                $perimeter = $parts[2];
                if ($tempPerimeterFilter === '') {
                    $tempPerimeterFilter = ' AND ( 0 ';
                }
                $tempPerimeterFilter .= " OR ( $field LIKE '" . CoreMysqli::get()->real_escape_string($perimeter) . "%' ) ";
            }
        }
        if ($tempPerimeterFilter !== '') {
            $tempPerimeterFilter .= ' ) ';
        }
        if ($tempPerimeterFilter === ' AND ( 0  ) ') {
            $tempPerimeterFilter = '';
        }

        $perimeterFilter .= $tempPerimeterFilter;
    }
}

if (!function_exists('createPerimeterFilter')) {
    /**
     * Cette fonction créé un morceau de clause WHERE permettant de filtrer
     * selon le périmètre d'habilitation de l'utilisateur.
     * Les roles à prendre en compte sont fournis pas le front-end via l'entête "Content-Type" :)
     * @param string $field Le nom du champ en base de données,
     * qui contient le périmètre. Par exemple : "\`{$_tablesPrefix}hierarchie\`.\`sdum\`".
     * @return null
     * <code>
     * createPerimeterFilter("`agents`.`sdum`");
     * </code>
     * @author Olivier Mansencal <olivier.mansencal@enedis.fr>
     * @copyright Copyright (c) 2021, Olivier Mansencal
     */
    function createPerimeterFilter($field)
    {
        global $_perimeterFilter, $_perimetre, $_myGrants;

        $apache_request_headers = apache_request_headers();
        $frontEndRequiredGrants = explode(
            '/',
            isolateText($apache_request_headers["Content-Type"] . '§', 'grants=', '§')
        );

        if (strpos($field, '`') === false) {
            $field = "`$field`";
        }

        $tempPerimeterFilter = '';
        foreach ($_myGrants as $_myGrant) {
            if (in_array($_myGrant, $frontEndRequiredGrants)) {
                $value = $_perimetre[$_myGrant];
                if ($tempPerimeterFilter === '') {
                    $tempPerimeterFilter = ' AND ( 0 ';
                }
                $tempPerimeterFilter .= " OR ( $field LIKE '" . CoreMysqli::get()->real_escape_string($value) . "%' ) ";
            }
        }
        if ($tempPerimeterFilter !== '') {
            $tempPerimeterFilter .= ' ) ';
        }
        if ($tempPerimeterFilter === ' AND ( 0  ) ') {
            $tempPerimeterFilter = '';
        }

        $_perimeterFilter .= $tempPerimeterFilter;
    }
}

if (!function_exists('getPerimeters')) {
    /**
     * Retourne les perimètres du rôle souhaité parmis la liste de l'ensemble des rôles passés en paramètre.
     *
     * @param string $roleNeeded Le rôle dont le programme souhaite connaitre les périmètres
     * @param string $userRoles L'enssemble des rôles de l'utilisateur,
     * dont le programme souhaite connaitre les périmètres associé (XXX_ROLE_PERIMETER).
     *
     * @return string[] Le tableau des périmètres des rôles passé en paramètre pour le rôle recherché.
     */
    function getPerimeters($roleNeeded, $userRoles)
    {
        $perimeters = array();
        if (preg_match_all('/' . preg_quote($roleNeeded, '/') . '_(\d+[A-Z]?)/', $userRoles, $matches)) {
            $perimeters = $matches[1];
        }

        return $perimeters;
    }
}

if (!function_exists('isolateText')) {
    /**
     * isolateText() retourne le texte contenu dans $text, situé entre $before et $after
     * @param string $text Le texte contenant la chaîne à extraire
     * @param string $before Le texte qui précède la chaîne à extraire
     * @param string $after Le texte qui suit la chaîne à extraire
     * @return string La chaîne extraite
     */
    function isolateText($text, $before, $after)
    {
        $tempString = strstr($text, $before);
        $tempString = substr($tempString, strlen($before));
        $positionAfter = strpos($tempString, $after);
        if ($positionAfter === false) {
            return '';
        } else {
            return substr($tempString, 0, $positionAfter);
        }
    }
}

if (!function_exists('cleaningBack')) {
    /**
     * cleaningBack() permet de nettoyer le tableau de retour en précisant quels entêtes sont à filtrer
     *
     * @param array $back Le tableau à retourner au client.
     * @param boolean $debug Ce booléen permet de ne pas filtrer les retours si il est vrai.
     * @param array $headers Les entêtes à retourner si debug est à false,
     * par defaut contient uniquement alertToDisplay.
     * @param boolean $download Si le retour devait être un fichier
     * seul alertToDisplay ou le fichier(pour téléchargement) serait à renvoyer au client.
     *
     * @return array Le tableau de retour trié ou non trié en mode debug.
     */
    function cleaningBack($back, $debug = false, $headers = array('alertToDisplay'), $download = false)
    {
        if ($debug) {
            return $back;
        }

        $render = array();
        foreach ($back as $key => $value) {
            if ($download) {
                if ($key === 'file' || $key === 'alertToDisplay') {
                    $render[$key] = $back[$key];
                }
            } else {
                if (in_array($key, $headers)) {
                    $render[$key] = $value;
                }
            }
        }

        return $render;
    }
}

if (!function_exists('mimeEncode')) {
    function mimeEncode($field, $value)
    {
        $prefs["input-charset"] = "UTF-8";
        $prefs["output-charset"] = "UTF-8";
        $prefs["line-length"] = 76;
        $prefs["line-break-chars"] = "\n";
        $prefs["scheme"] = "B";

        return iconv_mime_encode($field, $value, $prefs);
    }
}

if (!function_exists('removeAccentFromText')) {
    function removeAccentFromText($text)
    {
        $accentuatedChars = array(
            'é',
            'è',
            'ê',
            'ë',
            'à',
            'â',
            'ï',
            'î',
            'ô',
            'ù',
            'ç',
            'É',
            'È',
            'Ç',
            'À',
            'Ù',
            'Æ',
            'Ê',
            '¼'
        );
        $nonAccentuatedChars = array(
            'e',
            'e',
            'e',
            'e',
            'a',
            'a',
            'i',
            'i',
            'o',
            'u',
            'c',
            'E',
            'E',
            'C',
            'A',
            'U',
            'AE',
            'E',
            'OE'
        );

        return str_replace($accentuatedChars, $nonAccentuatedChars, $text);
    }
}

if (!function_exists('getRandomNumber')) {
    /**
     * getRandomNumber() permet de générer un nombre aléatoire.
     * Celui-ci sert à OASICE pour ne pas utiliser le cache du navigateur.
     */
    function getRandomNumber()
    {
        return random_int(1002051149010658, 9992051149010658);
    }
}

if (!function_exists('readCookies')) {
    /**
     * readCookies() permet de récupérer les cookies des entêtes HTTP d'une page WEB
     * @param string $header Le contenu de l'entête HTTP
     * @return string La chaîne contenant les cookies séparés par un point-virgule.
     */
    function readCookies($header)
    {
        $cookie = '';
        preg_match_all("!Cookie: ([^;\s]+)($|;)!", $header, $match);
        foreach ($match[1] as $val) {
            if ($val[0] === '=') {
                continue;
            }
            $cookie .= $val . ';';
        }

        return substr($cookie, 0, -1);
    }
}
$ch = null;
$CURLOPT_COOKIE = '';
$path_cookie = '';

if (!function_exists('httpGet')) {
    /**
     * httpGet() aspire le contenu d'une page Web.
     * @param string $url L'adresse de la page à aspirer
     * @param string $returnHeaders L'ordre de retourner les entêtes HTTP dans la réponse
     * @param string $returnHTML L'ordre de retourner le contenu HTML dans la réponse
     * @param string $postFields Les variables POST à envoyer au serveur
     * @param string $referer L'adresse du referer
     * @param string $headers Les entêtes de la demande. A noter : si une variable $headers existe déjà
     * dans le code appelant, la récupère aussi via Global.
     * @return string La chaîne contenant l'entête HTTP et/ou le contenu HTML
     * @copyright Enedis 2024
     * @author Olivier Mansencal <olivier.mansencal@enedis.fr>

     */
    function httpGet(
        $url,
        $returnHeaders = true,
        $returnHTML = 1,
        $postFields = null,
        $referer = null,
        $httpHeaders = null
    ) {
        global $CURLOPT_CONNECTTIMEOUT, $CURLOPT_TIMEOUT, $CURLOPT_USERAGENT, $CURLOPT_COOKIE;
        global $nombreDEssais, $path_cookie, $ch, $headers;
        !isset($CURLOPT_CONNECTTIMEOUT) && $CURLOPT_CONNECTTIMEOUT = 5;
        !isset($CURLOPT_TIMEOUT) && $CURLOPT_TIMEOUT = 300;
        !isset($CURLOPT_USERAGENT) && $CURLOPT_USERAGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0';
        !isset($nombreDEssais) && $nombreDEssais = 5;
        if (!$ch) {
            $ch = curl_init();
            if (php_sapi_name() !== 'cli') {
                $nni = isset($_SERVER) && array_key_exists('OIDC_CLAIM_uid', $_SERVER) ?
                    $_SERVER['OIDC_CLAIM_uid'] :
                    'cron';
                $path_cookie = __DIR__ . "/cookiejar_{$nni}.txt";
            } else {
                $path_cookie = __DIR__ . "/cookiejar_cli.txt";
            }
            touch($path_cookie);
            $path_cookie = realpath($path_cookie);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $CURLOPT_CONNECTTIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, $CURLOPT_TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, $CURLOPT_USERAGENT);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $returnHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $returnHTML);
        curl_setopt($ch, CURLOPT_COOKIEJAR, realpath($path_cookie));
        curl_setopt($ch, CURLOPT_COOKIEFILE, realpath($path_cookie));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        if ($headers !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($httpHeaders !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($referer !== null) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        if ($postFields !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }
        $essai = 0;
        do {
            $resultat = curl_exec($ch);
            $essai++;
            if ($resultat === false) {
                sleep(3);
            }
        } while (($resultat === false) && ($essai < $nombreDEssais));
        if ($resultat === false) {
            return false;
        } else {
            /**
             * L'extraction a réussi :
             * Recherche de l'apparition de nouveaux cookies le cas échéant
             */
            $newCookies = readCookies($resultat);
            if ($newCookies !== '') {
                if ($CURLOPT_COOKIE !== '') {
                    $CURLOPT_COOKIE .= ";";
                }
                $CURLOPT_COOKIE .= $newCookies;
            }

            /**
             * Retour de l'entête HTTP et/ou du contenu HTML
             */
            return $resultat;
        }
    }
}

define('EMPTY_DATE', '0000-00-00');
define('EMPTY_FR_DATE', '00/00/0000');

/**
 * Fonctions de date et autres formatages
 */

if (!function_exists('isDateUS8Formatted')) {
    function isDateUS8Formatted($date)
    {
        if (null === $date) {
            return false;
        }

        return substr($date, 4, 1) === '-' && substr($date, 7, 1) === '-' && strlen($date) >= 10 ? true : false;
    }
}

if (!function_exists('isDateUS6Formatted')) {
    function isDateUS6Formatted($date)
    {
        if (null === $date) {
            return false;
        }

        return substr($date, 2, 1) === '-' && substr($date, 5, 1) === '-' && strlen($date) === 8 ? true : false;
    }
}

if (!function_exists('isDateFrench8Formatted')) {
    function isDateFrench8Formatted($date)
    {
        if (null === $date) {
            return false;
        }

        return substr($date, 2, 1) === '/' && substr($date, 5, 1) === '/' && strlen($date) >= 10 ? true : false;
    }
}

if (!function_exists('isDateFrench6Formatted')) {
    function isDateFrench6Formatted($date)
    {
        if (null === $date) {
            return false;
        }

        return substr($date, 2, 1) === '/' && substr($date, 5, 1) === '/' && strlen($date) === 8 ? true : false;
    }
}

if (!function_exists('moveFrenchDateFrom6to8char')) {
    function moveFrenchDateFrom6to8char($date)
    {
        if (isDateFrench6Formatted($date)) {
            $fields = explode('/', $date);
            $year = $fields[2];
            $year >= '69'
                && $year = '19' . $year;
            $year < '70'
                && $year = '20' . $year;

            return implode('/', $fields);
        }

        return $date;
    }
}

if (!function_exists('moveUSDateFrom6to8char')) {
    function moveUSDateFrom6to8char($date)
    {
        if (isDateUS6Formatted($date)) {
            $fields = explode('-', $date);
            $year = $fields[0];
            $year >= '69'
                && $year = '19' . $year;
            $year < '70'
                && $year = '20' . $year;

            return implode('-', $fields);
        }

        return $date;
    }
}

if (!function_exists('dateFR')) {
    /**
     * dateFR() retourne une date au format français, année sur 4 chiffres.
     * @param string $date La date au format anglais / français, année sur 2 ou 4 chiffres.
     * @return string La date au format US / MySQL
     */
    function dateFR($date)
    {
        moveUSDateFrom6to8char($date);
        if (isDateUS8Formatted($date)) {
            return ($date && EMPTY_DATE !== $date) ? date('d/m/Y', strtotime($date)) : '';
        }
        moveFrenchDateFrom6to8char($date);
        if (isDateFrench8Formatted($date)) {
            return ($date && EMPTY_DATE !== $date) ? date('d/m/Y', strtotime($date)) : '';
        }

        return null;
    }
}

if (!function_exists('dateFR6')) {
    /**
     * dateFR6() retourne une date au format français, année sur 2 chiffres.
     * @param string $date La date au format anglais / français, année sur 2 ou 4 chiffres.
     * @return string La date au format US / MySQL
     */
    function dateFR6($date)
    {
        moveUSDateFrom6to8char($date);
        if (isDateUS8Formatted($date)) {
            return ($date && EMPTY_DATE !== $date) ? date('d/m/y', strtotime($date)) : '';
        }
        moveFrenchDateFrom6to8char($date);
        if (isDateFrench8Formatted($date)) {
            return (EMPTY_DATE !== $date) ? substr($date, 0, 6) . substr($date, 8, 2) : '';
        }

        return null;
    }
}

if (!function_exists('dateUS')) {
    /**
     * dateUS() retourne une date au format anglais
     * @param string $date La date au format anglais / français, année sur 2 ou 4 chiffres.
     * @return string La date au format US / MySQL
     */
    function dateUS($date)
    {
        moveUSDateFrom6to8char($date);
        if (isDateUS8Formatted($date)) {
            return ($date && EMPTY_DATE !== $date) ? date('Y-m-d', strtotime($date)) : '';
        }
        moveFrenchDateFrom6to8char($date);
        if (isDateFrench8Formatted($date)) {
            return ($date && EMPTY_FR_DATE !== $date) ? implode('-', array_reverse(explode('/', $date))) : '';
        }

        return null;
    }
}

if (!function_exists('dateUSnull')) {
    /**
     * dateUSnull() retourne une date au format anglais entourée par des guillemets, 'NULL' sinon
     * @param string $dateFR La date au format français.
     * @return string La date au format US / MySQL
     */
    function dateUSnull($date)
    {
        moveUSDateFrom6to8char($date);
        if (isDateUS8Formatted($date)) {
            return ($date && EMPTY_DATE !== $date) ?
                "'" . date('Y-m-d', strtotime($date)) . "'" :
                'NULL';
        }
        moveFrenchDateFrom6to8char($date);
        if (isDateFrench8Formatted($date)) {
            return ($date && EMPTY_FR_DATE !== $date) ?
                "'" . implode('-', array_reverse(explode('/', $date))) . "'" :
                'NULL';
        }

        return 'NULL';
    }
}

if (!function_exists('dateUS8')) {
    function dateUS8($date)
    {
        return dateUS($date);
    }
}

if (!function_exists('formatDate')) {
    /**
     * formatDate() retourne une date formatée
     * @param string $date La date au format anglais / français, année sur 2 ou 4 chiffres.
     * @return string $lang Le format local de sortie ("FR" => j/m/a, "US" => a-m-j)
     * @return integer $resultLength La longueur (6 => "24/12/1973", 8 => "24/12/73")
     */
    function formatDate($date, $lang = 'US', $resultLength = 8)
    {
        moveFrenchDateFrom6to8char($date);
        moveUSDateFrom6to8char($date);
        $function = 'date' . $lang;
        $resultLength === 6 && $function .= '6';

        return $$function($date);
    }
}

if (!function_exists('frenchDate')) {
    function frenchDate($format, $timestamp)
    {
        $result = date($format, $timestamp);

        $daysTranslation = [
            'Sunday'    => 'Dimanche',
            'Monday'    => 'Lundi',
            'Tuesday'   => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday'  => 'Jeudi',
            'Friday'    => 'Vendredi',
            'Saturday'  => 'Samedi'
        ];
        $result = str_replace(
            array_keys($daysTranslation),
            array_values($daysTranslation),
            $result
        );

        $monthsTranslation = [
            'January'   => 'Janvier',
            'February'  => 'Février',
            'March'     => 'Mars',
            'April'     => 'Avril',
            'May'       => 'Mai',
            'June'      => 'Juin',
            'July'      => 'Juillet',
            'August'    => 'Août',
            'September' => 'Septembre',
            'October'   => 'Octobre',
            'November'  => 'Novembre',
            'December'  => 'Décembre'
        ];
        $result = str_replace(
            array_keys($monthsTranslation),
            array_values($monthsTranslation),
            $result
        );

        // Handle the ordinal suffix for the day of the month (e.g., 1st, 2nd, 3rd)
        $result = preg_replace_callback(
            '/(\d{1,2})(st|nd|rd|th)/',
            function ($matches) {
                $number = $matches[1];

                return $number . ($number === 1 ? 'er' : '');
            },
            $result
        );

        return $result;
    }
}

if (!function_exists('dateTexte')) {
    function dateTexte($dateUS)
    {
        return ($dateUS > '' && EMPTY_DATE !== $dateUS) ? frenchDate('l jS F Y', $dateUS) : '';
    }
}

if (!function_exists('dateTexteSansAnnee')) {
    function dateTexteSansAnnee($dateUS)
    {
        return ($dateUS > '' && EMPTY_DATE !== $dateUS) ? frenchDate('l jS F', $dateUS) : '';
    }
}

$tableauJours = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
$tableauMois = array(
    'erreur',
    'Janv.',
    'Févr.',
    'Mars',
    'Avr.',
    'Mai',
    'Juin',
    'Juil.',
    'Août',
    'Sept.',
    'Oct.',
    'Nov.',
    'Déc.'
);

if (!function_exists('dateEleganteMessage')) {
    function dateEleganteMessage($dateUS)
    {
        global $tableauMois;
        if ($dateUS === '') {
            return '';
        }

        $tableauJours = array('Dim.', 'Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.');
        $timestamp = 0 + strtotime($dateUS);
        $cette_nuit = date('y.m.d');
        if (substr($dateUS, 0, 10) === date('Y-m-d')) {
            return "Aujourd'hui&nbsp;" . substr($dateUS, 11, 5);
        }

        if ((strtotime(str_replace('-', '.', $cette_nuit)) - $timestamp) < 24 * 3600) {
            return 'hier&nbsp;' . substr($dateUS, 11, 5);
        }
        if ((strtotime(str_replace('-', '.', $cette_nuit)) + $timestamp) < 24 * 3600) {
            return 'demain&nbsp;' . substr($dateUS, 11, 5);
        }
        $annee = substr($dateUS, 0, 4) + 0;
        $mois = substr($dateUS, 5, 2) + 0;
        $jour = substr($dateUS, 8, 2) + 0;
        $numJour = date("w", mktime(0, 0, 0, $mois, $jour, $annee));
        $jour_texte = strtolower($tableauJours[$numJour]);
        $mois_texte = strtolower($tableauMois[$mois]);
        if ($jour === 1) {
            $jour = '1er';
        }

        if ((strtotime('now') - $timestamp) < 365 * 24 * 3600) {
            return "$jour_texte&nbsp;$jour&nbsp;$mois_texte&nbsp;" . substr($dateUS, 11, 5);
        }

        return "$jour_texte&nbsp;$jour&nbsp;$mois_texte&nbsp;" . substr($dateUS, 11, 5) . '&nbsp;' . substr($dateUS, 0, 4);
    }
}

if (!function_exists('dateEleganteListe')) {
    function dateEleganteListe($dateUS)
    {
        $dateEleganteListe = dateFR6($dateUS); // Défaut : la date chiffrée au format français
        $timestamp = 0 + strtotime($dateUS);
        $cette_nuit = date('y.m.d');
        if (substr($dateUS, 0, 10) === date('Y-m-d')) {
            $dateEleganteListe =  substr($dateUS, 11, 5); // Aujourd'hui ? heure seule
        } elseif ((strtotime(str_replace('-', '.', $cette_nuit)) - $timestamp) < 24 * 3600) {
            $dateEleganteListe =  'hier';
        } elseif ((strtotime(str_replace('-', '.', $cette_nuit)) + $timestamp) < 24 * 3600) {
            $dateEleganteListe =  'demain';
        } elseif ((strtotime('now') - $timestamp) < 7 * 24 * 3600) {
            $dateEleganteListe =  frenchDate('l', $dateUS); // Pour les 7 derniers jour, le nom du jour
        }

        return $dateEleganteListe;
    }
}

if (!function_exists('dateTexteCourte')) {
    function dateTexteCourte($dateUS)
    {
        if ($dateUS > '' && EMPTY_DATE !== $dateUS) {
            $tableauJours = array('Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam');
            $annee = substr($dateUS, 0, 4) + 0;
            $anneeCourte = substr($dateUS, 2, 2);
            $mois = substr($dateUS, 5, 2) + 0;
            $jour = substr($dateUS, 8, 2);
            $numJour = date("w", mktime(0, 0, 0, $mois, $jour, $annee));
            $jour_texte = $tableauJours[$numJour];

            return $jour_texte . ' ' . substr($dateUS, 8, 2) . '/' . substr($dateUS, 5, 2) . '/' . $anneeCourte;
        } else {
            return '';
        }
    }
}

if (!function_exists('jourTexte')) {
    function jourTexte($dateUS)
    {
        if ($dateUS > '' && EMPTY_DATE !== $dateUS) {
            $tableauJours = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
            $annee = substr($dateUS, 0, 4) + 0;
            $mois = substr($dateUS, 5, 2) + 0;
            $jour = substr($dateUS, 8, 2) + 0;
            $numJour = date("w", mktime(0, 0, 0, $mois, $jour, $annee));

            return $tableauJours[$numJour];
        } else {
            return '';
        }
    }
}

if (!function_exists('dateTexteSansJour')) {
    function dateTexteSansJour($dateUS)
    {
        global $tableauMois;
        if ($dateUS > '' && EMPTY_DATE !== $dateUS) {
            $annee = substr($dateUS, 0, 4) + 0;
            $mois = substr($dateUS, 5, 2) + 0;
            $jour = substr($dateUS, 8, 2) + 0;
            $mois_texte = strtolower($tableauMois[$mois]);
            if ($jour === 1) {
                $jour = '1er';
            }

            return $jour . ' ' . $mois_texte . ' ' . $annee;
        } else {
            return '';
        }
    }
}

if (!function_exists('dateTexteCourteSansJour')) {
    function dateTexteCourteSansJour($dateUS)
    {
        return dateJourMois($dateUS);
    }
}

if (!function_exists('dateJourMois')) {
    function dateJourMois($dateUS)
    {
        global $tableauMois;
        if ($dateUS > '' && EMPTY_DATE !== $dateUS) {
            $mois = substr($dateUS, 5, 2) + 0;
            $jour = substr($dateUS, 8, 2) + 0;
            $mois_texte = strtolower($tableauMois[$mois]);
            if ($jour === 1) {
                $jour = '1er';
            }

            return $jour . ' ' . $mois_texte;
        } else {
            return '';
        }
    }
}

if (!function_exists('dateTexte2lignes')) {
    function dateTexte2lignes($dateUS)
    {
        global $tableauJours, $tableauMois;
        if ($dateUS > '' && EMPTY_DATE !== $dateUS) {
            $annee = substr($dateUS, 0, 4) + 0;
            $mois = substr($dateUS, 5, 2) + 0;
            $jour = substr($dateUS, 8, 2) + 0;
            $numJour = date("w", mktime(0, 0, 0, $mois, $jour, $annee));
            $jour_texte = $tableauJours[$numJour];
            $mois_texte = strtolower($tableauMois[$mois]);

            return $jour_texte . '<br />' . $jour . ' ' . $mois_texte . ' ' . $annee;
        } else {
            return '';
        }
    }
}

if (!function_exists('dateTexte3lignes')) {
    function dateTexte3lignes($dateUS)
    {
        global $tableauJours, $tableauMois;
        if ($dateUS > '' && EMPTY_DATE !== $dateUS) {
            $annee = substr($dateUS, 0, 4) + 0;
            $mois = substr($dateUS, 5, 2) + 0;
            $jour = substr($dateUS, 8, 2) + 0;
            $numJour = date("w", mktime(0, 0, 0, $mois, $jour, $annee));
            $jour_texte = $tableauJours[$numJour];
            $mois_texte = strtolower($tableauMois[$mois]);

            return $jour_texte . '<br />' . $jour . ' ' . $mois_texte . '<br />' . $annee;
        } else {
            return '';
        }
    }
}

if (!function_exists('dateIpilot')) {
    function dateIpilot($dateFR)
    {
        if ($dateFR > '' && EMPTY_FR_DATE !== $dateFR) {
            return substr($dateFR, 0, 2) . "-" . substr($dateFR, 3, 2) . "-" . substr($dateFR, 8, 2) . '%';
        } else {
            return '';
        }
    }
}

if (!function_exists('formatTelephone')) {
    function formatTelephone($numero)
    {
        if (strlen($numero) >= 9 && $numero[0] !== '0') {
            $numero = '0' . $numero;
        }
        $formatted = preg_replace("/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", "$1&nbsp;$2&nbsp;$3&nbsp;$4&nbsp;$5", $numero);

        return $formatted ?: '';
    }
}

if (!function_exists('formatNombre')) {
    function formatNombre(
        $number,
        $decimals = 0,
        $display_if_zero = false,
        $decimal_separator = ',',
        $thousands_separator = ' ',
        $nb_left = null
    ) {
        if ($number !== '0' || ($display_if_zero && $number === 0)) {
            $number = @number_format($number, $decimals, $decimal_separator, $thousands_separator);
            if ($nb_left) {
                if (strpos((string)$number, $decimal_separator) !== false) {
                    $parts = explode($decimal_separator, $number);
                    $intPart = (string)$parts[0];
                    $formattedIntPart = str_pad($intPart, $nb_left, '_', STR_PAD_LEFT);
                    $number = (string)$formattedIntPart . $decimal_separator . (string)$parts[1];
                } else {
                    $number = str_pad($number, $nb_left, '_', STR_PAD_LEFT);
                }
            }

            return $number;
        } else {
            return '';
        }
    }
}

if (!function_exists('registerApiCall')) {
    /**
     * Enregistre les appels API pour savoir quelles fonctionnalités sont utilisées ou non.
     * Les infos enregistrées seront dans le futur communiquées à applistats
     * pour leur utilisation dans le nouvel outil de monitoring.
     * Pour le moment on stocke dans la bdd de l'appli en question un triplet : (date, subject, nbre_appels)
     *
     * On ne compte que les appels à des subject propres à l'API de l'application,
     * les appels API au squelette sont ignorés.
     *
     * En cas d'erreur de requête, essai de création de la table car elle n'existe probablement pas.
     */
    function registerApiCall($subject, $success = true)
    {
        global $_tablesPrefix;
        $subject = CoreMysqli::get()->real_escape_string($subject);
        $errorCount = intval(!$success);
        $count = intval($success);
        $query =
            "INSERT INTO
                `{$_tablesPrefix}core_log_api` (`date`, `subject`, `count`, `error_count`)
            VALUES
                (CURDATE(), '$subject', $count, $errorCount)
            ON DUPLICATE KEY
                UPDATE `count` = `count` + $count, `error_count` = `error_count` + $errorCount;";

        return CoreMysqli::get()->query($query);
    }
}

if (!function_exists('buildTree')) {
    /**
     * buildTree() construit une vue hiérarchique
     * @param array $elements le tableau contenant toutes les données entrantes.
     * @param string $parentId le tableau contenant toutes les données entrantes.
     * @param string $idFieldName le nom de l'id des éléments du tableau.
     * @param string $parentIdFieldName le nom de l'id du parent des éléments du tableau.
     * @param string $childsFieldName le nom du tableau des enfants dans le tableau résultant.
     * @return array le tableau hiérarchisé.
     */
    function buildTree($elements, $parentId, $idFieldName = 'id', $parentIdFieldName = 'id_parent', $childsFieldName = 'childs')
    {
        $tree = [];
        foreach ($elements as $element) {
            if ($element[$parentIdFieldName] === $parentId) {
                $childs = buildTree($elements, $element[$idFieldName], $idFieldName, $parentIdFieldName, $childsFieldName);
                if ($childs) {
                    $element[$childsFieldName] = $childs;
                }
                $tree[] = $element;
            }
        }

        return $tree;
    }
}

if (!function_exists('httpSimpleResponse')) {
    /**
     * httpResponse
     *
     * /!\ Met fin au programme appelant et retourne code http avec contenu.
     *
     * @param  int $code
     * @param  string $message
     * @return void
     */
    function httpSimpleResponse($code, $message)
    {
        http_response_code($code);
        die($message);
    }
}

if (!function_exists('getCpuUsage')) {
    function getCpuUsage()
    {
        $load = sys_getloadavg();

        return round(100 * $load[0], 2); // Charge moyenne sur 1 minute
    }
}

if (!function_exists('getMemoryUsage')) {
    function getMemoryUsage()
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $freeArr = explode("\n", $free);
        $mem = preg_split("/\s+/", $freeArr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memoryUsage = $mem[2] / $mem[1] * 100;

        return round($memoryUsage, 2);
    }
}

if (!function_exists('getDiskUsage')) {
    function getDiskUsage()
    {
        $disk_free = disk_free_space("/");
        $disk_total = disk_total_space("/");
        $diskUsed = $disk_total - $disk_free;
        $diskUsage = ($diskUsed / $disk_total) * 100;

        return round($diskUsage, 2);
    }
}

if (!function_exists('saveMetrics')) {
    function saveMetrics($cpu, $memory, $disk)
    {
        global $_tablesPrefix;
        $table = "{$_tablesPrefix}core_metrics";
        $tableHistory = "{$_tablesPrefix}core_metrics_history";
        if (!CoreMysqli::get()->tableExists($table)) {
            CoreMysqli::get()->query(
                "CREATE TABLE `{$table}` (
                    `date_time` DATETIME,
                    `cpu_percent` FLOAT,
                    `memory_percent` FLOAT,
                    `disk_percent` FLOAT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;"
            );
        }

        $sql = "SELECT COUNT(*) AS `count` FROM `{$table}` WHERE DATE(`date_time`) < CURDATE()";
        $stmt = CoreMysqli::get()->query($sql);
        $rows = $stmt->fetch_all(MYSQLI_ASSOC);
        if ($rows[0]['count'] > 0) {
            if (!CoreMysqli::get()->tableExists($tableHistory)) {
                CoreMysqli::get()->query(
                    "CREATE TABLE `{$tableHistory}` (
                            `date` date NOT NULL,
                            `cpu_percent` float NOT NULL,
                            `memory_percent` float NOT NULL,
                            `disk_percent` float NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;"
                );
                CoreMysqli::get()->query("ALTER TABLE `{$tableHistory}` ADD PRIMARY KEY (`date`);");
            }
            CoreMysqli::get()->query(
                "INSERT IGNORE INTO
                        `{$tableHistory}`
                        (`date`, `cpu_percent`, `memory_percent`, `disk_percent`)
                    SELECT
                        DATE(`date_time`),
                        AVG(`cpu_percent`),
                        AVG(`memory_percent`),
                        AVG(`disk_percent`)
                    FROM
                        `{$table}`
                    WHERE
                        DATE(`date_time`) < CURDATE()
                    GROUP BY
                        DATE(`date_time`)"
            );
            CoreMysqli::get()->query("DELETE FROM `{$table}` WHERE DATE(`date_time`) < CURDATE()");
        }

        $sql = "INSERT INTO `{$table}` (date_time, cpu_percent, memory_percent, disk_percent) VALUES (NOW(), ?, ?, ?)";
        $stmt = CoreMysqli::get()->prepare($sql);
        $stmt->bind_param('ddd', $cpu, $memory, $disk);
        $stmt->execute();
    }
}

if (!function_exists('getDevServerBaseUrl')) {
    /**
     * getDevServerBaseUrl Retourne l'URL du serveur de développement.
     *
     * @return string L'URL du serveur de développement
     * (ex: https://omtbzh-dev.place-cloud-enedis.fr ou https://paco-dev.enedis.fr)
     * @throws CoreException Si la variable d'environnement BASE_URL n'est pas définie
     * ou si le format de l'URL est incorrect
     */
    function getDevServerBaseUrl(): string
    {
        $baseUrl = getenv('BASE_URL') ?? null;

        if (!$baseUrl) {
            throw new CoreException("La variable d'environnement 'BASE_URL' n'est pas définie.");
        }

        $parsedUrl = parse_url($baseUrl);
        if (!isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            throw new CoreException("Le format de l'URL dans 'BASE_URL' est incorrect.");
        }

        $scheme = $parsedUrl['scheme'];
        $host = $parsedUrl['host'];
        $path = $parsedUrl['path'] ?? '';

        if (str_contains($host, '-dev.')) {
            return $baseUrl;
        }

        $hostParts = explode('.', $host, 2);
        if (count($hostParts) !== 2) {
            throw new CoreException("Le format du domaine est incorrect.");
        }

        $devHost = "{$hostParts[0]}-dev.{$hostParts[1]}";

        return "{$scheme}://{$devHost}{$path}";
    }
}

if (!function_exists('getProdServerBaseUrl')) {
    /**
     * getProdServerBaseUrl Retourne l'URL du serveur de production à partir de l'URL de développement.
     * eg.: https://omtbzh.place-cloud-enedis.fr or https://paco.enedis.fr
     *
     * @return string L'URL du serveur de production
     * @throws CoreException Si la variable d'environnement BASE_URL n'est pas définie
     * ou si le format de l'URL est incorrect
     */
    function getProdServerBaseUrl(): string
    {
        $baseUrl = getenv('BASE_URL') ?? null;

        if (!$baseUrl) {
            throw new CoreException("La variable d'environnement 'BASE_URL' n'est pas définie.");
        }

        $parsedUrl = parse_url($baseUrl);
        if (!isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            throw new CoreException("Le format de l'URL dans 'BASE_URL' est incorrect.");
        }

        $scheme = $parsedUrl['scheme'];
        $host = $parsedUrl['host'];
        $path = $parsedUrl['path'] ?? '';

        if (!str_contains($host, '-dev.') && !str_contains($host, '-poc.')) {
            return $baseUrl;
        }

        $hostParts = explode('-dev.', $host, 2);
        if (count($hostParts) !== 2) {
            $hostParts = explode('-poc.', $host, 2);
            if (count($hostParts) !== 2) {
                throw new CoreException("Le format du domaine est incorrect.");
            }
        }

        $prodHost = $hostParts[0] . '.' . $hostParts[1];

        return "{$scheme}://{$prodHost}{$path}";
    }
}

if (!function_exists('getFrontendJsVersion')) {
    function getFrontEndJsVersion(): string
    {
        return isolateText(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'  . DIRECTORY_SEPARATOR . 'index.html'),
            'script src="main.',
            '.js'
        );
    }
}
