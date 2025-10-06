<?php

declare(strict_types=1);

namespace EnedisLabBZH\Core\CoreDbSynchronize;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;

class CoreTableImporter
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function import(): void
    {
        $sqlScript = file($this->filePath);
        $query = '';
        foreach ($sqlScript as $line) {
            $startWith = substr(trim($line), 0, 2);
            $endWith = substr(trim($line), -1, 1);

            if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//') {
                continue;
            }

            $query .= $line;
            if ($endWith == ';') {
                try {
                    $result = CoreMysqli::get()->query($query);
                    if ($result === false) {
                        throw new CoreException("Error executing query: {$query}");
                    }
                    CoreMysqli::get()->commit();
                } catch (CoreException $e) {
                    CoreMysqli::get()->rollback();

                    throw $e;
                }
                $query = '';
            }
        }
    }
}
