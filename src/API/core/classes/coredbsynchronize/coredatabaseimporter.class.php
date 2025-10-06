<?php

declare(strict_types=1);

namespace EnedisLabBZH\Core\CoreDbSynchronize;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreDbSynchronize\CoreTableImporter;
use ZipArchive;

class CoreDatabaseImporter
{
    private string $zipFilePath;
    private string $extractDir;

    public function __construct(string $zipFilePath, string $extractDir)
    {
        $this->zipFilePath = $zipFilePath;
        $this->extractDir = $extractDir;
    }

    public function import(): void
    {
        echo "Extraciting zip file ... ";
        $this->extractZipFile();
        echo "done!<br>\n";
        $sqlFiles = glob($this->extractDir . '/*.sql');

        foreach ($sqlFiles as $sqlFile) {
            echo 'Importing ' . pathinfo($sqlFile)['filename'] . '... ';
            $importer = new CoreTableImporter($sqlFile);
            $importer->import();
            echo "done!<br>\n";
        }

        echo "Cleaning temp files ... ";
        $this->cleanupExtractedFiles();
        $this->cleanupZipFile();
        echo "done!<br>\n";
    }

    private function extractZipFile(): void
    {
        $zip = new ZipArchive();
        if ($zip->open($this->zipFilePath) !== true) {
            throw new CoreException("Unable to open ZIP file: {$this->zipFilePath}");
        }
        $zip->extractTo($this->extractDir);
        $zip->close();
    }

    private function cleanupExtractedFiles(): void
    {
        $files = glob($this->extractDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->extractDir);
    }

    private function cleanupZipFile(): void
    {
        if (is_file($this->zipFilePath)) {
            unlink($this->zipFilePath);
        }
    }
}
