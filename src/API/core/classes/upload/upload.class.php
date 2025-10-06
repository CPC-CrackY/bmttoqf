<?php

namespace EnedisLabBZH\Core;

/**
 * This class aims to safely handle uploaded files.
 *
 * Example of usage :
 *
 * require_once Upload.php;
 *
 * $uploadHandler = new Upload();
 *
 * $uploadHandler->execute();
 *
 * $filePath = getSanitizedFilePath();
 */
class Upload
{
    private $allowedTypes;

    private $uploadFolder;

    // options
    private $fileAttributeName;
    private $newFileName;
    private $restrictedExtensions;
    private $maxFileSizeInMegaBytes;

    private $fileSize;
    private $tmpPath;
    private $sanitizedFilePath;
    private $extension;
    private $mime;

    public function __construct()
    {
        $this->uploadFolder = dirname(__FILE__) . "/upload";

        // This line will be removed in a future release :
        $this->allowedTypes = [
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'pdf' => ['application/pdf'],
            'csv' => ['application/csv', 'text/csv', 'text/plain'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xlsm' => [
                'application/vnd.ms-excel.sheet.macroEnabled.12',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'msg' => ['application/vnd.ms-outlook', 'application/CDFV2-unknown']
        ];

        $this->restrictedExtensions = array_keys($this->allowedTypes);
    }

    /**
     * Change allowed types
     * Examples are :
     * 'png' => ['image/png'],
     * 'jpg' => ['image/jpeg'],
     * 'jpeg' => ['image/jpeg'],
     * 'pdf' => ['application/pdf'],
     * 'csv' => ['application/csv', 'text/csv', 'text/plain'],
     * 'xls' => ['application/vnd.ms-excel'],
     * 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
     * 'xlsm' => ['application/vnd.ms-excel.sheet.macroEnabled.12',
     *            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
     * 'doc' => ['application/msword'],
     * 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
     * 'ppt' => ['application/vnd.ms-powerpoint'],
     * 'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
     * 'msg' => ['application/vnd.ms-outlook', 'application/CDFV2-unknown']
     */
    public function setAllowedTypes(array $allowedTypes)
    {
        $this->allowedTypes = $allowedTypes;
    }

    /**
     * Optional: to call before handleUpload()
     */
    public function restrictMaxFileSize($maxSizeInMegaBytes)
    {
        $this->maxFileSizeInMegaBytes = $maxSizeInMegaBytes;
    }

    /**
     * Optional: to call before handleUpload()
     */
    public function restrictExtensions($restrictedExtensions)
    {
        if (!is_array($restrictedExtensions) || count($restrictedExtensions) === 0) {
            throw new CoreException("\$allowedExtensions must be a full array", 2);
        }

        $unauthorizedExtentions = array_diff($restrictedExtensions, array_keys($this->allowedTypes));
        if (count($unauthorizedExtentions) > 0) {
            throw new CoreException(
                "\$allowedExtensions contains forbidden elements: " . implode(", ", $unauthorizedExtentions),
                LEVEL_ERROR
            );
        }

        $this->restrictedExtensions = $restrictedExtensions;
    }

    /**
     * Optional: to call before handleUpload()
     *
     * If not set, the first encountered file in $_FILES will be processed by handleUpload().
     */
    public function setFileAttributeName($fileAttributeName)
    {
        $this->fileAttributeName = $fileAttributeName;
    }

    /**
     * Optional: to call before handleUpload()
     *
     * If not set, the file will kept the posted filename
     * and it will be moved to the default upload folder.
     */
    public function setNewFileName($newFileName)
    {
        $this->newFileName = $newFileName;
    }

    /**
     * Optional: to call before handleUpload()
     *
     * If not set, the file will kept the posted filename
     * and it will be moved to the default upload folder.
     */
    public function setUploadFolder($uploadFolder)
    {
        $this->uploadFolder = $uploadFolder;
    }

    /**
     * Safely handle uploaded files.
     */
    public function execute()
    {
        try {
            $this->handleFilesObject();

            $this->checkSizeLimitation();
            $this->checkFileType();

            $this->createUploadFolder();

            $success = move_uploaded_file($this->tmpPath, $this->sanitizedFilePath);
            if (!$success) {
                throw new CoreException("Invalid tmp file.", LEVEL_ERROR);
            }
        } catch (\Throwable $th) {
            if ($this->tmpPath && file_exists($this->tmpPath)) {
                unlink($this->tmpPath);
            }

            throw $th;
        }
    }

    /**
     * To call after handleUpload().
     */
    public function getSanitizedFilePath()
    {
        return $this->sanitizedFilePath;
    }

    /**
     * To call after handleUpload().
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * To call after handleUpload().
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * To call after handleUpload().
     */
    public function getFileName()
    {
        return basename($this->sanitizedFilePath);
    }

    /**
     * To call after handleUpload().
     */
    public function getExtension()
    {
        return $this->extension;
    }

    public function getMaxUploadSizeInMegaBytes()
    {
        return min(
            str_replace(ini_get("upload_max_filesize"), "M", ""),
            str_replace(ini_get("post_max_size"), "M", ""),
            $this->maxFileSizeInMegaBytes
        );
    }

    /**
     * Parse $_FILES to set
     * - $this->fileSize
     * - $this->tmpPath
     * - $this->sanitizedFilePath
     * - $this->extension
     */
    private function handleFilesObject()
    {
        $this->checkFilesObjectError($_FILES);

        if ($this->fileAttributeName && !array_key_exists($this->fileAttributeName, $_FILES)) {
            throw new CoreException("Missing file attribute: {$this->fileAttributeName}", LEVEL_ERROR);
        }
        if ($this->fileAttributeName) {
            $fileArray = $_FILES[$this->fileAttributeName];
        } else {
            foreach ($_FILES as $fileData) {
                if (is_array($fileData) && array_key_exists('tmp_name', $fileData)) {
                    $fileArray = $fileData;
                }
            }
        }

        $this->checkFilesObjectError($fileArray);

        $this->fileSize = $fileArray['size'];

        $this->tmpPath = realpath($fileArray['tmp_name']);

        $this->sanitizedFilePath =
            $this->uploadFolder . "/" . $this->removeIllegalCharacters(
                $this->newFileName
                    ? $this->newFileName
                    : $fileArray['name']
            );

        $pathinfo = pathinfo($this->sanitizedFilePath);
        $this->extension = strtolower($pathinfo['extension']);
    }

    private function checkFilesObjectError($uploadedData)
    {

        if (!is_array($uploadedData)) {
            throw new CoreException("Wrong data format", LEVEL_ERROR);
        }
        if (!array_key_exists('error', $uploadedData)) {
            return;
        }

        switch ($uploadedData['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new CoreException('No file sent.', LEVEL_ERROR);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new CoreException('Exceeded filesize limit.', LEVEL_ERROR);
            default:
                throw new CoreException("Error during uploading", LEVEL_ERROR);
        }
    }

    private function checkSizeLimitation()
    {
        if (
            $this->maxFileSizeInMegaBytes
            && $this->fileSize <= $this->maxFileSizeInMegaBytes * 1024 * 1024
        ) {
            throw new CoreException("File size exceeds the custom limit.", LEVEL_ERROR);
        }
    }

    private function checkFileType()
    {

        if (!in_array($this->extension, $this->restrictedExtensions)) {
            throw new CoreException(
                "File extension is not allowed : {$this->extension}",
                LEVEL_ERROR
            );
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->mime = finfo_file($finfo, $this->tmpPath);
        finfo_close($finfo);
        if (!in_array($this->mime, $this->allowedTypes[$this->extension])) {
            throw new CoreException("Unauthorized file type", LEVEL_ERROR);
        }
    }

    public function removeIllegalCharacters($rawFilename)
    {
        $illegalCharacters = array_merge(
            array_map('chr', range(0, 31)),
            ["<", ">", ":", '"', "/", "\\", "|", "?", "*", " "]
        );

        return str_replace($illegalCharacters, "-", $rawFilename);
    }

    private function createUploadFolder()
    {
        $uploadFolder = dirname($this->sanitizedFilePath);
        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }
    }
}
