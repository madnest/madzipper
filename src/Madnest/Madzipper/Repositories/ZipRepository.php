<?php

namespace Madnest\Madzipper\Repositories;

use Exception;
use ZipArchive;

class ZipRepository implements RepositoryInterface
{
    private $archive;

    public bool $open = false;

    /**
     * Construct with a given path.
     *
     * @param $filePath
     * @param bool $create
     * @param ZipArchive $archive
     *
     * @throws \Exception
     *
     * @return ZipRepository
     */
    public function __construct($filePath, $create = false, $archive = null)
    {
        // Check if ZipArchive is available
        if (! class_exists('ZipArchive')) {
            throw new Exception('Error: Your PHP version is not compiled with zip support');
        }

        $this->archive = $archive ? $archive : new ZipArchive();

        $this->open($filePath, $create);
    }

    /**
     * Open the archive.
     *
     * @param mixed $filePath
     * @param bool|int $flags Any constant from ZipArchive (e.g `ZipArchive::CREATE` or `ZipArchive::OVERWRITE`)
     * @return void
     * @throws Exception
     */
    protected function open($filePath, $flags = null): void
    {
        if ($flags === true) {
            // backward compatibility for pre-PHP8
            $flags = ZipArchive::CREATE;
        }

        $res = $this->archive->open($filePath, $flags);

        if ($res !== true) {
            throw new Exception("Error: Failed to open $filePath! Error: ".$this->getErrorMessage($res));
        }

        $this->open = true;
    }

    /**
     * Check if the archive is open.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open ? true : false;
    }

    /**
     * Check if the archive is closed.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return ! $this->open ? true : false;
    }

    /**
     * Add a file to the opened Archive.
     *
     * @param $pathToFile
     * @param $pathInArchive
     */
    public function addFile($pathToFile, $pathInArchive)
    {
        $this->archive->addFile($pathToFile, $pathInArchive);
    }

    /**
     * Add an empty directory.
     *
     * @param $dirName
     */
    public function addEmptyDir($dirName)
    {
        $this->archive->addEmptyDir($dirName);
    }

    /**
     * Add a file to the opened Archive using its contents.
     *
     * @param $name
     * @param $content
     */
    public function addFromString($name, $content)
    {
        $this->archive->addFromString($name, $content);
    }

    /**
     * Remove a file permanently from the Archive.
     *
     * @param $pathInArchive
     */
    public function removeFile($pathInArchive)
    {
        $this->archive->deleteName($pathInArchive);
    }

    /**
     * Get the content of a file.
     *
     * @param $pathInArchive
     *
     * @return string
     */
    public function getFileContent($pathInArchive)
    {
        return $this->archive->getFromName($pathInArchive);
    }

    /**
     * Get the stream of a file.
     *
     * @param $pathInArchive
     *
     * @return mixed
     */
    public function getFileStream($pathInArchive)
    {
        return $this->archive->getStream($pathInArchive);
    }

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item.
     *
     * @param $callback
     */
    public function each($callback)
    {
        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            //skip if folder
            $stats = $this->archive->statIndex($i);
            if ($stats['size'] === 0 && $stats['crc'] === 0) {
                continue;
            }
            $callback($this->archive->getNameIndex($i), $this->archive->statIndex($i));
        }
    }

    /**
     * Checks whether the file is in the archive.
     *
     * @param $fileInArchive
     *
     * @return bool
     */
    public function fileExists($fileInArchive)
    {
        return $this->archive->locateName($fileInArchive) !== false;
    }

    /**
     * Sets the password to be used for decompressing
     * function named usePassword for clarity.
     *
     * @param $password
     *
     * @return bool
     */
    public function usePassword($password)
    {
        return $this->archive->setPassword($password);
    }

    /**
     * Returns the status of the archive as a string.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->archive->getStatusString();
    }

    /**
     * Closes the archive and saves it.
     *
     * @return bool
     */
    public function close(): bool
    {
        $this->open = false;

        return $this->archive->close();
    }

    /**
     * Get error message.
     *
     * @param mixed $resultCode
     * @return string
     */
    private function getErrorMessage($resultCode): string
    {
        switch ($resultCode) {
            case ZipArchive::ER_EXISTS:
                return 'ZipArchive::ER_EXISTS - File already exists.';
            case ZipArchive::ER_INCONS:
                return 'ZipArchive::ER_INCONS - Zip archive inconsistent.';
            case ZipArchive::ER_MEMORY:
                return 'ZipArchive::ER_MEMORY - Malloc failure.';
            case ZipArchive::ER_NOENT:
                return 'ZipArchive::ER_NOENT - No such file.';
            case ZipArchive::ER_NOZIP:
                return 'ZipArchive::ER_NOZIP - Not a zip archive.';
            case ZipArchive::ER_OPEN:
                return 'ZipArchive::ER_OPEN - Can\'t open file.';
            case ZipArchive::ER_READ:
                return 'ZipArchive::ER_READ - Read error.';
            case ZipArchive::ER_SEEK:
                return 'ZipArchive::ER_SEEK - Seek error.';
            default:
                return "An unknown error [$resultCode] has occurred.";
        }
    }
}
