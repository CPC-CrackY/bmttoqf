<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">

<head>
    <title>Test Message API</title>
    <meta charset="UTF-8">
</head>
<style>
    body {
        font-family: Arial;
    }

    label {
        font-weight: bold;
    }

    input[type="text"] {
        width: 500px;
    }

    textarea {
        height: 300px;
        width: 1200px;
    }
</style>

<body>
    <form action="#" method="post">
        <label>Numéro de portable :</label><br>
        <input type="text" name="mobile" placeholder="06xxxxxxxx" value="<?php echo @$_POST['mobile'] ?>"><br><br>
        <label>Adresse e-mail :</label><br>
        <input type="text" name="email" placeholder="e-mail" value="<?php echo @$_POST['email'] ?>"><br><br>
        <input type="submit" value="Envoyer SMS et/ou e-mail"><br><br>
        <hr>
    </form>
    <?php

    include_once __DIR__ . '/../../../core_include.php'; // for $_tablesPrefix
    include_once __DIR__ . '/../../../../my-config.inc.php'; // for $_tablesPrefix
    require_once __DIR__ . '/../../../autoload.php';

    use EnedisLabBZH\Core\Apigile\ApiMessage;

    try {
        /**
         * Start counter to measure total execution time
         */
        $time_start = microtime(true);
        $apiMessage = new ApiMessage();
        if (@$_POST['email']) {
            echo '<br><br><label>Résultat e-mail :</label><br>';
            echo " <textarea>\n\n";

            try {
                $result = $apiMessage->sendEmail(
                    'Enedis_interne',
                    [$_POST['email']],
                    'Test objet',
                    'e-mail envoyé à ' . date('H:i:s')
                );
                echo json_encode([$result], JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                echo '(' . $e->getCode() . ") : " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
            }
            echo '</textarea> ';
        }
        if (@$_POST['mobile']) {
            echo '<br><br><label>Résultat SMS :</label><br>';
            echo "<textarea>\n\n";

            try {
                $result = $apiMessage->sendSms(
                    'Enedis_interne',
                    [$_POST['mobile']],
                    'SMS envoyé à ' . date('H:i:s')
                );
                echo json_encode([$result], JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                echo '(' . $e->getCode() . ") : " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
            }
            echo '</textarea>';
        }
    } catch (Exception $e) {

        echo "<textarea>\n\n";
        echo "(" . $e->getCode() . ") : " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
        echo '</textarea>';

        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
    ?>
</body>

</html>