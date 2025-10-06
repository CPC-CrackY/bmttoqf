<?php

namespace EnedisLabBZH\Core;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;

require_once __DIR__ . '/../../core_functions.php';

/**
 * Class for managing application parameters in MySQL database using MySQLi.
 */
class CoreParameters
{
    private $table;

    /**
     * Constructor for the class.
     */
    public function __construct($_tablesPrefix = null)
    {
        !defined('TABLE_PREFIX')
            && define('TABLE_PREFIX', '_tablesPrefix');
        $_tablesPrefix !==  null
            && $this->table = $_tablesPrefix . 'core_parameters';
        (array_key_exists(TABLE_PREFIX, $GLOBALS))
            && $GLOBALS[TABLE_PREFIX] !==  null
            && $this->table = $GLOBALS[TABLE_PREFIX] . 'core_parameters';
        if (!$this->table) {
            throw new CoreException('Missing $_tablesPrefix');
        }

        !CoreMysqli::get()->tableExists($this->table)
            && \updateDatabaseIfNeeded();
    }

    /**
     * return an array of all parameters.
     * @return array(mixed) Parameters.
     */
    public function getParameters()
    {
        $query = "SELECT name, value, type, description, hidden FROM {$this->table}";
        $result = CoreMysqli::get()->query($query);

        return $result->fetch_all(MYSQLI_ASSOC);
    }
    /**
     * save all parameters.
     * @return array(mixed) Parameters.
     */
    public function saveParameters($parameters): bool
    {
        try {
            foreach ($parameters as $parameter) {
                if ('' === $parameter['value']) {
                    $query = "DELETE FROM {$this->table} WHERE name = ?";
                    $stmt = CoreMysqli::get()->prepare($query);
                    $stmt->bind_param('s', $parameter['name']);
                } else {
                    $query = "UPDATE {$this->table} SET value = ? WHERE name = ?";
                    $stmt = CoreMysqli::get()->prepare($query);
                    $stmt->bind_param('ss', $parameter['value'], $parameter['name']);
                }
                $result = $stmt->execute();
                !$result && die('error ' . $query);
            }
            $stmt->close();

            return true;
        } catch (\Exception $e) {
            error_log('On saveParameters: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return false;
        }
    }

    /**
     * Get the value of a parameter.
     * @param string $name Parameter name.
     * @return mixed Parameter value, or null if the name does not exist.
     */
    public function getParameter($name, $description = '', $hidden = false)
    {
        $query = "SELECT value, type FROM {$this->table} WHERE name = ?";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('s', $name);
        $stmt->execute();

        $value = $type = null;
        $stmt->bind_result($value, $type);
        $stmt->fetch();
        $stmt->close();
        if (null === $value) {
            $this->setParameter($name, '', $description, $hidden);

            return $this->getParameter($name);
        }

        'array' === $type || 'object' === $type
            && $value = json_decode($value);

        return $value;
    }


    /**
     * Set the value of a parameter.
     * @param string $name       Parameter name.
     * @param mixed  $value      Parameter value.
     * @return bool True if the update or insertion was successful, false otherwise.
     */
    public function setParameter($name, $value, $description = '', $hidden = false)
    {
        $hidden = $hidden ? 1 : 0;
        $type = gettype($value);
        'array' === $type || 'object' === $type
            && $value = json_encode($value);
        $query = "INSERT INTO {$this->table} (name, value, type, description, hidden)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  value = VALUES(value), type = VALUES(type), description = VALUES(description), hidden = VALUES(hidden)";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('ssssi', $name, $value, $type, $description, $hidden);
        $res = $stmt->execute();
        $stmt->close();

        return $res;
    }

    /**
     * Set the value of a parameter.
     * @param string $name       Parameter name.
     * @param mixed  $value      Parameter value.
     * @return bool True if the update or insertion was successful, false otherwise.
     */
    public function deleteParameter($name)
    {
        $query = "DELETE FROM {$this->table} WHERE name = ?";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('s', $name);
        $res = $stmt->execute();
        $stmt->close();
        if (!$res) {
            throw new CoreException("Unable to delete parameter {$name} : " . CoreMysqli::get()->error);
        }
    }

    /**
     * parameterExists() ask if a parameter exists
     * @param string $name Parameter name.
     * @return bool true if parameter already exists.
     */
    public function parameterExists($name)
    {
        $query = "SELECT name FROM {$this->table} WHERE name = ?";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('s', $name);
        $stmt->execute();

        $value = null;
        $stmt->bind_result($value);
        $stmt->fetch();
        $stmt->close();

        return $value === $name;
    }
}
