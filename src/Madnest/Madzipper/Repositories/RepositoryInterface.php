<?php

namespace Madnest\Madzipper\Repositories;

/**
 * RepositoryInterface that needs to be implemented by every Repository.
 *
 * Class RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * Construct with a given path.
     *
     * @param  bool  $new
     */
    public function __construct($filePath, $new = false, $archiveImplementation = null);

    /**
     * Check if the archive is open.
     */
    public function isOpen(): bool;

    /**
     * Check if the archive is closed.
     */
    public function isClosed(): bool;

    /**
     * Add a file to the opened Archive.
     */
    public function addFile($pathToFile, $pathInArchive);

    /**
     * Add a file to the opened Archive using its contents.
     */
    public function addFromString($name, $content);

    /**
     * Add an empty directory.
     */
    public function addEmptyDir($dirName);

    /**
     * Remove a file permanently from the Archive.
     */
    public function removeFile($pathInArchive);

    /**
     * Get the content of a file.
     *
     *
     * @return string
     */
    public function getFileContent($pathInArchive);

    /**
     * Get the stream of a file.
     *
     *
     * @return mixed
     */
    public function getFileStream($pathInArchive);

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item.
     */
    public function each($callback);

    /**
     * Checks whether the file is in the archive.
     *
     *
     * @return bool
     */
    public function fileExists($fileInArchive);

    /**
     * Sets the password to be used for decompressing.
     *
     *
     * @return bool
     */
    public function usePassword($password);

    /**
     * Returns the status of the archive as a string.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Closes the archive and saves it.
     */
    public function close();
}
