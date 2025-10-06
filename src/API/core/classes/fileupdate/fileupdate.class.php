<?php

namespace EnedisLabBZH\Core;

use EnedisLabBZH\Core\CoreException;

/**
 * Merge folders from a source path to a destination path recursively.
 *
 * After merging, source folders will be kept. By default, source files will be deleted.
 */
class FileUpdate
{
    private const ROOT_PATH = "/var/www/html";

    private $isSrcFileDeletion = true;

    public function setIsSrcFileDeletion($isSrcFileDeletion)
    {
        $this->isSrcFileDeletion = $isSrcFileDeletion;
    }

    /**
     * Copy folders and move files from a source path to a destination path recursively.
     *
     * @param  string $sourcePath
     * @param  string $destPath
     * @return void
     */
    public function checkUpdate(string $sourcePath, string $destPath = FileUpdate::ROOT_PATH): void
    {
        $this->validatePath($sourcePath);
        $this->validatePath($destPath);

        $this->updateRecursively($sourcePath, $destPath);
    }

    /**
     * updateRecursively
     *
     * @param  string $sourcePath
     * @param  string $destPath
     * @return void
     */
    private function updateRecursively(string $sourcePath, string $destPath)
    {

        !is_dir($destPath)
            && mkdir($destPath, 0777, true);

        ($items = scandir($sourcePath))
            || throw new CoreException("Fail to scan {$sourcePath}");

        foreach ($items as $item) {

            if (in_array($item, ['.', '..'])) {
                continue;
            }

            $itemPath = "{$sourcePath}/{$item}";

            if (is_dir($itemPath)) {
                $this->updateRecursively($itemPath, "{$destPath}/{$item}");

                continue;
            }

            copy($itemPath, "{$destPath}/{$item}");

            if ($this->isSrcFileDeletion) {
                unlink($itemPath);
            }
        }
    }

    /**
     * validatePath
     *
     * @param  string $path
     * @return void
     */
    private function validatePath(string &$path)
    {
        $this->sanitizePath($path);
        $this->checkPath($path);
    }

    /**
     * sanitizePath
     *
     * @param  string $unsafePath
     * @return void
     */
    private function sanitizePath(string &$path): void
    {
        $path = realpath($path);
    }


    /**
     * checkPath
     *
     * @param  string $path
     * @return void
     */
    private function checkPath(string $path)
    {
        !str_contains($path, FileUpdate::ROOT_PATH)
            && throw new CoreException("The provided path {$path} must start with " . FileUpdate::ROOT_PATH);
    }
}
