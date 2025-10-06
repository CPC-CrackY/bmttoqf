<?php

declare(strict_types=1);

namespace EnedisLabBZH\Core\CoreDbSynchronize;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;
use EnedisLabBZH\Core\CoreDbSynchronize\CoreTableExporter;
use EnedisLabBZH\Core\CoreDbSynchronize\CoreZipManager;

class CorePartialDatabaseExporter
{
    private array $tables;
    private CoreZipManager $coreZipManager;

    public function __construct(string $zipFilePath, $tables  = null)
    {
        $this->tables = [];

        $tables
            && $this->tables = $tables;

        if (empty($this->tables)) {
            !defined('TABLE_PREFIX')
                && define('TABLE_PREFIX', '_tablesPrefix');
            (array_key_exists(TABLE_PREFIX, $GLOBALS))
                && $GLOBALS[TABLE_PREFIX] !==  null
                && $table = $GLOBALS[TABLE_PREFIX] . 'core_tables_to_export';
            if (!$table) {
                throw new CoreException('Missing $_tablesPrefix');
            }

            !CoreMysqli::get()->tableExists($table)
                && \updateDatabaseIfNeeded();

            $query = "SELECT `name` FROM `{$table}` ORDER BY `name`";
            $stmt = CoreMysqli::get()->prepare($query);
            $stmt->execute();
            $name = null;
            $stmt->bind_result($name);
            while ($stmt->fetch()) {
                $this->tables[] = $name;
            }
        }

        $this->coreZipManager = new CoreZipManager($zipFilePath);
    }

    public function create(): void
    {
        echo 'Initializing... ';
        $this->coreZipManager->create();
        echo "done!<br>\n";

        foreach ($this->tables as $table) {
            echo 'Exporting ' . $table . '... ';
            $exporter = new CoreTableExporter($table);
            $content = $exporter->export();
            $this->coreZipManager->addFromString($table . '.sql', $content);
            echo "done!<br>\n";
        }

        echo 'Closing Zip file... ';
        $this->coreZipManager->close();
        echo "done!<br>\n";
    }
}
