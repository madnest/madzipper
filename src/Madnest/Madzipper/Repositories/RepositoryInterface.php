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
    public function __construct(string $filePath, bool $create = false, mixed $archive = null);

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
    public function addFile(string $pathToFile, string $pathInArchive): void;

    /**
     * Add a file to the opened Archive using its contents.
     */
    public function addFromString(string $name, string $content): void;

    /**
     * Add an empty directory.
     */
    public function addEmptyDir(string $dirName): void;

    /**
     * Remove a file permanently from the Archive.
     */
    public function removeFile(string $pathInArchive): void;

    /**
     * Get the content of a file.
     */
    public function getFileContent(string $pathInArchive): string|false;

    /**
     * Get the stream of a file.
     */
    public function getFileStream(string $pathInArchive): mixed;

    /**
     * Will loop over every item in the archive and will execute the callback on them
     * Will provide the filename for every item.
     */
    public function each(callable $callback): void;

    /**
     * Checks whether the file is in the archive.
     */
    public function fileExists(string $fileInArchive): bool;

    /**
     * Sets the password to be used for decompressing.
     */
    public function usePassword(string $password): bool;

    /**
     * Returns the status of the archive as a string.
     */
    public function getStatus(): string|false;

    /**
     * Closes the archive and saves it.
     */
    public function close(): bool;
}
