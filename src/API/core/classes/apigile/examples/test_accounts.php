<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <title>Test APIGILE accounts</title>
    <meta charset="UTF-8">
</head>
<style>
    table,
    tr,
    td,
    th {
        border-collapse: collapse;
        border: 1px solid #333;
    }
</style>

<body>
    <form action="#" method="POST">
        <input type="text" name="phone" placeholder="Phone number" value="<?php echo @$_POST['phone']; ?>"><br />
        <input type="text" name="email" placeholder="e-mail address" value="<?php echo @$_POST['email']; ?>"><br />
        <input type="submit"><br />
    </form>

    <pre>
        <?php
        $prod = true;
        $testContacts = true;
        $testOrganizations = true;
        $testSMS = (bool)@$_POST['phone'];
        $testEmail = (bool)@$_POST['email'];

        $pause = 0;

        include_once __DIR__ . '/../../../../my-config.inc.php'; // for $_tablesPrefix
        require_once __DIR__ . '/../../../autoload.php';

        use EnedisLabBZH\Core\Apigile\ApiContacts;
        use EnedisLabBZH\Core\Apigile\ApiOrganizations;
        use EnedisLabBZH\Core\Apigile\ApiMessage;
        use EnedisLabBZH\Core\CoreException;

        $app = htmlentities(str_replace('-dev', '', explode('.', getenv('HOSTNAME'))[0]));

        try {
            ob_implicit_flush(true);
            while (@ob_get_level() > 0) {
                @ob_end_flush();
            }
            flush();

            /**
             * Start counter to measure total execution time
             */
            $time_start = microtime(true);
            echo '<table><tr><th>App</td>';

            if ($testContacts) {
                echo '<td>Contacts</td>';
            }
            if ($testOrganizations) {
                echo '<td>Organizations</td>';
            }
            if ($testSMS) {
                echo '<td>SMS</td>';
            }
            if ($testEmail) {
                echo '<td>e-Mails</td>';
            }
            echo '</tr>';
            define('ERROR_401', 'error: Oauth2 json returned code 401!');
            define('ERROR_403', 'error: Oauth2 json returned code 403!');
            define('ERROR_503', 'error: Oauth2 json returned code 503!');
            /**
             * Initiate Contacts API
             */

            echo '<tr><td>' . $app . '</td><td>';
            flush();

            define('BEGIN_SPAN', '<span title="');
            define('END_SPAN', '">⁉️</span>');
            if ($testContacts) {
                try {
                    $apiContact = new ApiContacts();

                    try {
                        $employees = $apiContact->searchByNNI('F29645');
                        echo (false !== strpos(json_encode($employees), 'MANSENCAL')) ?
                            '✔️' :
                            '<span title="not found">⁉️</span>';
                    } catch (CoreException $e) {
                        if ($e->getMessage() === ERROR_403) {
                            echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for contacts API!">❌</span>';
                        } elseif ($e->getMessage() === ERROR_503) {
                            echo BEGIN_SPAN . __LINE__ . " : $app " . 'can\'t send query to contacts API!">⁉️</span>';
                        } else {
                            echo BEGIN_SPAN . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                        }
                    }
                } catch (CoreException $e) {
                    if ($e->getMessage() === ERROR_403) {
                        echo ' <span title="' . __LINE__ . " : $app " . 'is not authorized for contacts API!">❌</span>';
                    } elseif ($e->getMessage() === ERROR_503) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'can\'t send query to contacts API!">⁉️</span>';
                    } else {
                        echo BEGIN_SPAN . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                    }
                }

                echo '</td><td> ';
            }

            if ($testOrganizations) {
                try {
                    $apiOrganizations = new ApiOrganizations();
                    $apiOrganizations->selectDR('DR BRETAGNE');
                    $orgs = $apiOrganizations->getOrganizations();
                    echo (false !== strpos(json_encode($orgs), 'BRETAGNE')) ? '✔️' : '<span title="not found">⁉️</span>';
                } catch (CoreException $e) {
                    if ($e->getMessage() === ERROR_403) {
                        echo '  <span title="' . __LINE__ . " : $app " . 'is not authorized for organizations API!">❌</span>';
                    } elseif ($e->getMessage() === ERROR_503) {
                        echo '   <span title="' . __LINE__ . " : $app " . 'can\'t send query to organizations API!">⁉️</span>';
                    } else {
                        echo '    <span title="' . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                    }
                }

                echo '</td><td>  ';
            }

            if ($testSMS || $testEmail) {
                try {
                    $apiMessage = new ApiMessage();
                } catch (CoreException $e) {
                    if ($e->getMessage() === ERROR_403) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for messages API!">❌</span>';
                    } elseif ($e->getMessage() === ERROR_503) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'can\'t send SMS!">⁉️</span>';
                    } else {
                        echo BEGIN_SPAN . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                    }
                }
            }

            if ($testSMS) {
                try {
                    try {
                        $apiMessage->sendSms(
                            'Enedis_interne',
                            [$_POST['phone']],
                            'Test message ' . $app,
                            'noreply@relation-client-enedis.fr'
                        );
                        echo BEGIN_SPAN . htmlentities(json_encode($apiMessage)) . '">✔️</span>';
                    } catch (CoreException $e) {
                        if ($e->getMessage() === ERROR_403) {
                            echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for sending SMS!">❌</span>';
                        } elseif ($e->getMessage() === ERROR_503) {
                            echo BEGIN_SPAN . __LINE__ . " : $app " . 'can\'t send SMS!">⁉️</span> ';
                        } elseif ($e->getMessage() === ERROR_401) {
                            echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for sending SMS!">❌</span>';
                        } else {
                            echo BEGIN_SPAN . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                        }
                    }
                } catch (CoreException $e) {
                    if ($e->getMessage() === ERROR_403) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for messages API!">❌</span>';
                    } elseif ($e->getMessage() === ERROR_503) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'can\'t send SMS!">⁉️</span>  ';
                    } else {
                        echo BEGIN_SPAN . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                    }
                }

                echo '</td><td>   ';
            }

            if ($testEmail) {
                try {
                    $apiMessage->sendEmail(
                        category: 'Enedis_interne',
                        recipients: [$_POST['email']],
                        subject: 'Test objet ' . $app,
                        message: 'Test message ' . $app
                    );
                    echo BEGIN_SPAN . htmlentities(json_encode($apiMessage)) . '">✔️</span>';
                } catch (CoreException $e) {
                    if ($e->getMessage() === ERROR_403) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for sending e-mails!">❌</span>';
                    } elseif ($e->getMessage() === ERROR_503) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'can\'t send e-mails!">⁉️</span>';
                    } elseif ($e->getMessage() === ERROR_401) {
                        echo BEGIN_SPAN . __LINE__ . " : $app " . 'is not authorized for sending e-mails!">❌</span>';
                    } else {
                        echo BEGIN_SPAN . __LINE__ . ' : ' . $app . $e->getMessage() . END_SPAN;
                    }
                }

                echo "</td>";
            }
            echo "</tr>\n";
            flush();
            sleep($pause);
        } catch (CoreException $e) {

            error_log($e->getMessage());
            error_log($e->getTraceAsString());

            echo $e->getMessage();
            echo $e->getTraceAsString();
        }
