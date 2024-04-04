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
    public function __construct($filePath, $new = false, $archiveImplementation = null)
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
    public function addFile($pathToFile, $pathInArchive)
    {
        $this->entries[$pathInArchive] = $pathInArchive;
    }

    /**
     * Add a file to the opened Archive using its contents.
     */
    public function addFromString($name, $content)
    {
        $this->entries[$name] = $name;
    }

    /**
     * Remove a file permanently from the Archive.
     */
    public function removeFile($pathInArchive)
    {
        unset($this->entries[$pathInArchive]);
    }

    /**
     * Get the content of a file.
     *
     *
     * @return string
     */
    public function getFileContent($pathInArchive)
    {
        return $this->entries[$pathInArchive];
    }

    /**
     * Get the stream of a file.
     *
     *
     * @return mixed
     */
    public function getFileStream($pathInArchive)
    {
        return $this->entries[$pathInArchive];
    }

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item.
     */
    public function each($callback)
    {
        foreach ($this->entries as $entry) {
            call_user_func_array($callback, [
                'file' => $entry,
            ]);
        }
    }

    /**
     * Checks whether the file is in the archive.
     *
     *
     * @return bool
     */
    public function fileExists($fileInArchive)
    {
        return array_key_exists($fileInArchive, $this->entries);
    }

    /**
     * Returns the status of the archive as a string.
     *
     * @return string
     */
    public function getStatus()
    {
        return 'OK';
    }

    /**
     * Closes the archive and saves it.
     */
    public function close()
    {
    }

    /**
     * Add an empty directory.
     */
    public function addEmptyDir($dirName)
    {
        // CODE...
    }

    /**
     * Sets the password to be used for decompressing.
     */
    public function usePassword($password)
    {
        // CODE...
    }
}
