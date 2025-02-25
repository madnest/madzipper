<?php

namespace Madnest\Madzipper\Repositories;

use Exception;
use ZipArchive;

class ZipRepository implements RepositoryInterface
{
    private ?ZipArchive $archive = null;

    public bool $open = false;

    /**
     * Construct with a given path.
     *
     * @throws \Exception
     */
    public function __construct(string $filePath, bool $create = false, mixed $archive = null)
    {
        // Check if ZipArchive is available
        if (! class_exists('ZipArchive')) {
            throw new Exception('Error: Your PHP version is not compiled with zip support');
        }

        $this->archive = $archive ? $archive : new ZipArchive;

        $this->open($filePath, $create);
    }

    /**
     * Open the archive.
     *
     * @throws Exception
     */
    protected function open(string $filePath, bool $create = false): void
    {
        $res = $this->archive->open($filePath, ($create ? ZipArchive::CREATE : 0));

        if ($res !== true) {
            throw new Exception("Error: Failed to open {$filePath}! Error: ".$this->getErrorMessage($res));
        }

        $this->open = true;
    }

    /**
     * Check if the archive is open.
     */
    public function isOpen(): bool
    {
        return $this->open ? true : false;
    }

    /**
     * Check if the archive is closed.
     */
    public function isClosed(): bool
    {
        return ! $this->open ? true : false;
    }

    /**
     * Add a file to the opened Archive.
     */
    public function addFile(string $pathToFile, string $pathInArchive): void
    {
        $this->archive->addFile($pathToFile, $pathInArchive);
    }

    /**
     * Add an empty directory.
     */
    public function addEmptyDir(string $dirName): void
    {
        $this->archive->addEmptyDir($dirName);
    }

    /**
     * Add a file to the opened Archive using its contents.
     */
    public function addFromString(string $name, string $content): void
    {
        $this->archive->addFromString($name, $content);
    }

    /**
     * Remove a file permanently from the Archive.
     */
    public function removeFile(string $pathInArchive): void
    {
        $this->archive->deleteName($pathInArchive);
    }

    /**
     * Get the content of a file.
     *
     * @return string
     */
    public function getFileContent(string $pathInArchive): string|false
    {
        return $this->archive->getFromName($pathInArchive);
    }

    /**
     * Get the stream of a file.
     */
    public function getFileStream(string $pathInArchive): mixed
    {
        return $this->archive->getStream($pathInArchive);
    }

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item.
     */
    public function each(callable $callback): void
    {
        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            // skip if folder
            $stats = $this->archive->statIndex($i);
            if ($stats['size'] === 0 && $stats['crc'] === 0) {
                continue;
            }
            $callback($this->archive->getNameIndex($i), $this->archive->statIndex($i));
        }
    }

    /**
     * Checks whether the file is in the archive.
     */
    public function fileExists(string $fileInArchive): bool
    {
        return $this->archive->locateName($fileInArchive) !== false;
    }

    /**
     * Sets the password to be used for decompressing
     * function named usePassword for clarity.
     */
    public function usePassword(string $password): bool
    {
        return $this->archive->setPassword($password);
    }

    /**
     * Returns the status of the archive as a string.
     */
    public function getStatus(): string|false
    {
        return $this->archive->getStatusString();
    }

    /**
     * Closes the archive and saves it.
     */
    public function close(): bool
    {
        $this->open = false;

        return $this->archive->close();
    }

    /**
     * Get error message.
     *
     * @param  mixed  $resultCode
     */
    private function getErrorMessage(int $resultCode): string
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
                return "An unknown error [{$resultCode}] has occurred.";
        }
    }
}
