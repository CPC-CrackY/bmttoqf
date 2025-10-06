<?php

namespace EnedisLabBZH\MyDemoApp;

use EnedisLabBZH\Core\CoreException;

/**
 * Class for managing application parameters in MySQL database using MySQLi.
 */
class Test
{
    /**
     * __construct
     *
     * @param string $clientId Apigile credentials
     * @param string $clientSecret Apigile credentials
     * @param string $endpointApi url of the endpoint API
     * @return void
     */
    public function __construct()
    {
    }

    public function setText($text)
    {
        if (null === $text) {
            throw new CoreException('$text can\'t be null');
        }
        session_status() !== PHP_SESSION_ACTIVE
            && session_start();
        $_SESSION['text'] = $text;
    }

    public function getText()
    {
        session_status() !== PHP_SESSION_ACTIVE
            && session_start();

        return $_SESSION['text'];
    }
}
