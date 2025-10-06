<?php

namespace EnedisLabBZH\Core;

use Exception;
use mysqli;

/**
 * Class for managing access to MySQL database using MySQLi.
 */
final class CoreMysqliHandler extends mysqli
{
    /**
     * existsTable returns true is the table exists, false elsewhere.
     *
     * @param  string $table
     * @return boolean
     */
    final public function tableExists($table)
    {
        $stmt = $this->prepare("SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_NAME = ?");
        $stmt->bind_param("s", $table);
        $stmt->execute();
        $stmt->bind_result($tableType);
        $stmt->fetch();

        return ('BASE TABLE' === $tableType) ? true : false;
    }
}

final class CoreMysqli
{
    private static $coreMysqliHandlerInstance = null;

    private function __construct()
    {
    }

    final public static function &init($hostname, $username, $password, $database)
    {

        is_null(self::$coreMysqliHandlerInstance)
            && self::$coreMysqliHandlerInstance = new CoreMysqliHandler($hostname, $username, $password, $database);

        return self::$coreMysqliHandlerInstance;
    }

    final public static function &get()
    {

        return self::$coreMysqliHandlerInstance;
    }

    public static function escapeString($string)
    {
        if ($string === null) {
            return null;
        }

        return self::get()->real_escape_string($string);
    }
}
