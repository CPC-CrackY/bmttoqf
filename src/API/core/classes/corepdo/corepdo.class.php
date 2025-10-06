<?php

namespace EnedisLabBZH\Core;

use PDO;

define('DNS_KEYS', [
    "Driver",
    "Server",
    "Port",
    "Database",
    "sslmode"
]);

define('LOGIN_KEYS', [
    "Uid",
    "Pwd"
]);

/**
 * Extends PDO class with handy constructor.
 *
 * PREREQUISITE:
 * - extensions "pdo_odbc" and "odbc" (place-cloud.conf)
 */
class CorePDO extends PDO
{
    /**
     * Generate a new CorePDO instance.
     *
     * @param array $params : contains the following keys :
     * Driver, Server, Port, Database, sslmode, Uid, Pwd
     * @return void
     */
    public function __construct($params)
    {
        $dnsStrings = [];
        foreach (DNS_KEYS as $key) {
            $this->checkKeyExists($key, $params);
            $dnsStrings[] = "{$key}=" . $params[$key];
        }

        foreach (LOGIN_KEYS as $key) {
            $this->checkKeyExists($key, $params);
        }

        parent::__construct(
            "odbc:" . implode(";", $dnsStrings),
            $params["Uid"],
            $params["Pwd"]
        );
    }

    private function checkKeyExists($key, $params)
    {
        if (!array_key_exists($key, $params)) {
            throw new CoreException("Parameter {$key} is required.");
        }
    }
}
