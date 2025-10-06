<?php

declare(strict_types=1);

namespace EnedisLabBZH\Core\CoreDbSynchronize;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;

class CoreTableExporter
{
    private string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    public function export(): string
    {
        $structure = $this->getTableStructure();
        $data = $this->getTableData();

        return $this->formatExport($structure, $data);
    }

    private function getTableStructure(): string
    {
        $result = CoreMysqli::get()->query("SHOW CREATE TABLE `{$this->tableName}`");
        $row = $result->fetch_assoc();

        if (!isset($row['Create Table'])) {
            throw new CoreException("Unable to retrieve table structure for {$this->tableName}");
        }

        return "SET FOREIGN_KEY_CHECKS = 0;\n\nDROP TABLE IF EXISTS `{$this->tableName}`;\n" . $row['Create Table'];
    }

    private function getTableData(): array
    {
        $result = CoreMysqli::get()->query("SELECT * FROM `{$this->tableName}`");
        $data = [];

        while ($row = $result->fetch_assoc()) {
            // $data[] = array_map([CoreMysqli::class, 'escapeString'], $row);
            $data[] = $row;
        }

        return $data;
    }

    private function formatExport($structure, $data)
    {
        $export = "-- Structure de la table `{$this->tableName}`\n\n";
        $export .= $structure . ";\n\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
        $export .= "-- Données de la table `{$this->tableName}`\n\n";

        $batchSize = 100;
        $rowsToInsert = [];
        $anonymizer = new Anonymizer();

        // Récupérer les configurations d'anonymisation pour la table en cours
        $anonymizationConfig = $this->getAnonymizationConfig();

        foreach ($data as $row) {
            $escapedValues = array_map(function ($key, $value) use ($anonymizer, $anonymizationConfig) {
                if ($value === null) {
                    return 'NULL';
                }
                // Appliquer l'anonymisation si nécessaire
                if ($value !== '' && isset($anonymizationConfig[$key])) {
                    $config = $anonymizationConfig[$key];
                    $method = @$config['anonymizationMethod'];
                    if ($method) {
                        $value = $anonymizer->$method($value, $config['anonymizationConfig']);
                    }
                }

                return "'" . str_replace(
                    ["\r\n", "\n", "\r"],
                    ["\\r\\n", "\\n", "\\r"],
                    CoreMysqli::get()->real_escape_string($value)
                ) . "'";
            }, array_keys($row), $row);

            $rowsToInsert[] = "(" . implode(", ", $escapedValues) . ")";

            if (count($rowsToInsert) >= $batchSize) {
                $export .= "INSERT INTO `{$this->tableName}` VALUES " . implode(", ", $rowsToInsert) . ";\n";
                $rowsToInsert = [];
            }
        }

        if (!empty($rowsToInsert)) {
            $export .= "INSERT INTO `{$this->tableName}` VALUES " . implode(", ", $rowsToInsert) . ";\n";
        }

        return $export;
    }

    private function getAnonymizationConfig()
    {
        $table = null;
        !defined('TABLE_PREFIX')
            && define('TABLE_PREFIX', '_tablesPrefix');
        (array_key_exists(TABLE_PREFIX, $GLOBALS))
            && $GLOBALS[TABLE_PREFIX] !==  null
            && $table = $GLOBALS[TABLE_PREFIX] . 'core_fields_anonymization';
        if (!$table) {
            throw new CoreException('Missing $_tablesPrefix');
        }
        $query = "SELECT field_name, anonymize, anonymizationMethod, anonymizationConfig
                  FROM `{$table}`
                  WHERE table_name = ?";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param("s", $this->tableName);
        $stmt->execute();
        $result = $stmt->get_result();

        $config = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['anonymize']) {
                $config[$row['field_name']] = [
                    'anonymizationMethod' => $row['anonymizationMethod'],
                    'anonymizationConfig' => json_decode($row['anonymizationConfig'])
                ];
            }
        }

        return $config;
    }

    public function downloadExport(): void
    {
        $content = $this->export();
        $filename = $this->tableName . '_export_' . date('Y-m-d_H-i-s') . '.sql';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
    }
}
