<?php

namespace Madnest\Madzipper\Tests;

use Madnest\Madzipper\Repositories\RepositoryInterface;

class ArrayArchive implements RepositoryInterface
{
    private $entries = [];

    /**
     * Construct with a given path.
     *
     * @param  bool  $new
     */
    public function __construct(string $filePath, bool $create = false, mixed $archive = null)
    {
    }

    /**
     * Check if the archive is open.
     */
    public function isOpen(): bool
    {
        return true;
    }

    /**
     * Check if the archive is closed.
     */
    public function isClosed(): bool
    {
        return false;
    }

    /**
     * Add a file to the opened Archive.
     */
    public function addFile(string $pathToFile, string $pathInArchive): void
    {
        $this->entries[$pathInArchive] = $pathInArchive;
    }

    /**
     * Add a file to the opened Archive using its contents.
     */
    public function addFromString(string $name, string $content): void
    {
        $this->entries[$name] = $name;
    }

    /**
     * Remove a file permanently from the Archive.
     */
    public function removeFile(string $pathInArchive): void
    {
        unset($this->entries[$pathInArchive]);
    }

    /**
     * Get the content of a file.
     */
    public function getFileContent(string $pathInArchive): string|false
    {
        return $this->entries[$pathInArchive];
    }

    /**
     * Get the stream of a file.
     */
    public function getFileStream(string $pathInArchive): mixed
    {
        return $this->entries[$pathInArchive];
    }

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item.
     */
    public function each(callable $callback): void
    {
        foreach ($this->entries as $entry) {
            call_user_func_array($callback, [
                'file' => $entry,
            ]);
        }
    }

    /**
     * Checks whether the file is in the archive.
     */
    public function fileExists(string $fileInArchive): bool
    {
        return array_key_exists($fileInArchive, $this->entries);
    }

    /**
     * Returns the status of the archive as a string.
     */
    public function getStatus(): string
    {
        return 'OK';
    }

    /**
     * Closes the archive and saves it.
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Add an empty directory.
     */
    public function addEmptyDir(string $dirName): void
    {
        // CODE...
    }

    /**
     * Sets the password to be used for decompressing.
     */
    public function usePassword(string $password): bool
    {
        // CODE...
    }
}
