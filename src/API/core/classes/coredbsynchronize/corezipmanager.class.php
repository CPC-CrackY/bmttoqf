<?php

declare(strict_types=1);

namespace EnedisLabBZH\Core\CoreDbSynchronize;

use ZipArchive;
use EnedisLabBZH\Core\CoreException;

class CoreZipManager
{
    private ZipArchive $zip;
    private string $zipFilePath;

    public function __construct(string $zipFilePath)
    {
        $this->zipFilePath = $zipFilePath;
        $this->zip = new ZipArchive();
    }

    public function create(): void
    {
        file_exists($this->zipFilePath) && unlink($this->zipFilePath);
        if ($this->zip->open($this->zipFilePath, ZipArchive::CREATE) !== true) {
            throw new CoreException("Unable to create ZIP file: {$this->zipFilePath}");
        }
    }

    public function open(): void
    {
        if ($this->zip->open($this->zipFilePath) !== true) {
            throw new CoreException("Unable to open ZIP file: {$this->zipFilePath}");
        }
    }

    public function addFile(string $filePath, string $entryName = ''): void
    {
        if (empty($entryName)) {
            $entryName = basename($filePath);
        }
        if (!$this->zip->addFile($filePath, $entryName)) {
            throw new CoreException("Unable to add file to ZIP: {$filePath}");
        }
    }

    public function addFromString(string $entryName, string $content): void
    {
        if (!$this->zip->addFromString($entryName, $content)) {
            throw new CoreException("Unable to add content to ZIP as: {$entryName}");
        }
    }

    public function extractTo(string $destination): void
    {
        if (!$this->zip->extractTo($destination)) {
            throw new CoreException("Unable to extract ZIP to: {$destination}");
        }
    }

    public function close(): void
    {
        $this->zip->close();
    }

    public function getFileContents(string $entryName): string
    {
        $contents = $this->zip->getFromName($entryName);
        if ($contents === false) {
            throw new CoreException("Unable to read file from ZIP: {$entryName}");
        }

        return $contents;
    }

    public function getFilesList(): array
    {
        $filesList = [];
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $filesList[] = $this->zip->getNameIndex($i);
        }

        return $filesList;
    }
}
