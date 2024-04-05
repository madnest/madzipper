<?php

namespace Madnest\Madzipper;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Madnest\Madzipper\Repositories\RepositoryInterface;

/**
 * This Madzipper class is a wrapper around the ZipArchive methods with some handy functions.
 *
 * Class Madzipper
 */
class Madzipper
{
    /**
     * Constant for extracting.
     */
    const WHITELIST = 1;

    /**
     * Constant for extracting.
     */
    const BLACKLIST = 2;

    /**
     * Constant for matching only strictly equal file names.
     */
    const EXACT_MATCH = 4;

    /**
     * @var string Represents the current location in the archive
     */
    private $currentFolder = '';

    /**
     * @var Filesystem Handler to the file system
     */
    private $file;

    /**
     * @var RepositoryInterface Handler to the archive
     */
    private $repository;

    /**
     * @var string The path to the current zip file
     */
    private $filePath;

    /**
     * Constructor.
     *
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs = null)
    {
        $this->file = $fs ? $fs : new Filesystem();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (is_object($this->repository) && $this->repository->isOpen()) {
            $this->repository->close();
        }
    }

    /**
     * Create a new zip Archive if the file does not exists
     * opens a zip archive if the file exists.
     *
     * @param $pathToFile string The file to open
     * @param RepositoryInterface|string $type The type of the archive, defaults to zip, possible are zip, phar
     *
     * @throws \RuntimeException
     * @throws Exception
     * @throws \InvalidArgumentException
     *
     * @return $this Madzipper instance
     */
    public function make($pathToFile, $type = 'zip'): self
    {
        $new = $this->createArchiveFile($pathToFile);

        $objectOrName = $type;
        if (is_string($type)) {
            $objectOrName = 'Madnest\Madzipper\Repositories\\'.ucwords($type).'Repository';
        }

        if (! is_subclass_of($objectOrName, 'Madnest\Madzipper\Repositories\RepositoryInterface')) {
            throw new \InvalidArgumentException("Class for '{$objectOrName}' must implement RepositoryInterface interface");
        }

        try {
            if (is_string($objectOrName)) {
                $this->repository = new $objectOrName($pathToFile, $new);
            } else {
                $this->repository = $type;
            }
        } catch (Exception $e) {
            throw $e;
        }

        $this->filePath = $pathToFile;

        return $this;
    }

    /**
     * Create a new zip archive or open an existing one.
     *
     * @param string $pathToFile
     * @throws Exception
     * @return self
     */
    public function zip(string $pathToFile): self
    {
        $this->make($pathToFile);

        return $this;
    }

    /**
     * Create a new phar file or open one.
     *
     * @param string $pathToFile
     * @throws Exception
     * @return self
     */
    public function phar(string $pathToFile): self
    {
        $this->make($pathToFile, 'phar');

        return $this;
    }

    /**
     * Create a new rar file or open one.
     *
     * @param string $pathToFile
     * @throws Exception
     * @return self
     */
    public function rar(string $pathToFile): self
    {
        $this->make($pathToFile, 'rar');

        return $this;
    }

    /**
     * Extracts the opened zip archive to the specified location <br/>
     * you can provide an array of files and folders and define if they should be a white list
     * or a black list to extract. By default this method compares file names using "string starts with" logic.
     *
     * @param $path string The path to extract to
     * @param array $files       An array of files
     * @param int   $methodFlags The Method the files should be treated
     * @throws Exception
     * @return void
     */
    public function extractTo($path, array $files = [], $methodFlags = self::BLACKLIST): void
    {
        if (! $this->file->exists($path) && ! $this->file->makeDirectory($path, 0755, true)) {
            throw new \RuntimeException('Failed to create folder');
        }

        if ($methodFlags & self::EXACT_MATCH) {
            $matchingMethod = function ($haystack) use ($files) {
                return in_array($haystack, $files, true);
            };
        } else {
            $matchingMethod = function ($haystack) use ($files) {
                return Str::startsWith($haystack, $files);
            };
        }

        if ($methodFlags & self::WHITELIST) {
            $this->extractFilesInternal($path, $matchingMethod);
        } else {
            // blacklist - extract files that do not match with $matchingMethod
            $this->extractFilesInternal($path, function ($filename) use ($matchingMethod) {
                return ! $matchingMethod($filename);
            });
        }
    }

    /**
     * Extracts matching files/folders from the opened zip archive to the specified location.
     *
     * @param string $extractToPath The path to extract to
     * @param string $regex         regular expression used to match files. See @link http://php.net/manual/en/reference.pcre.pattern.syntax.php
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function extractMatchingRegex($extractToPath, $regex)
    {
        if (empty($regex)) {
            throw new \InvalidArgumentException('Missing pass valid regex parameter');
        }

        $this->extractFilesInternal($extractToPath, function ($filename) use ($regex) {
            $match = preg_match($regex, $filename);
            if ($match === 1) {
                return true;
            } elseif ($match === false) {
                //invalid pattern for preg_match raises E_WARNING and returns FALSE
                //so if you have custom error_handler set to catch and throw E_WARNINGs you never end up here
                //but if you have not - this will throw exception
                throw new \RuntimeException("regular expression match on '$filename' failed with error. Please check if pattern is valid regular expression.");
            }

            return false;
        });
    }

    /**
     * Gets the content of a single file if available.
     *
     * @param $filePath string The full path (including all folders) of the file in the zip
     *
     * @throws Exception
     *
     * @return mixed returns the content or throws an exception
     */
    public function getFileContent($filePath)
    {
        if ($this->repository->fileExists($filePath) === false) {
            throw new Exception(sprintf('The file "%s" cannot be found', $filePath));
        }

        return $this->repository->getFileContent($filePath);
    }

    /**
     * Add one or multiple files to the zip.
     *
     * @param $pathToAdd array|string An array or string of files and folders to add
     * @param null|mixed $fileName
     *
     * @return $this Madzipper instance
     */
    public function add($pathToAdd, $fileName = null)
    {
        if (is_array($pathToAdd)) {
            foreach ($pathToAdd as $key => $dir) {
                if (! is_int($key)) {
                    $this->add($dir, $key);
                } else {
                    $this->add($dir);
                }
            }
        } elseif ($this->file->isFile($pathToAdd)) {
            if ($fileName) {
                $this->addFile($pathToAdd, $fileName);
            } else {
                $this->addFile($pathToAdd);
            }
        } else {
            $this->addDir($pathToAdd);
        }

        return $this;
    }

    /**
     * Add an empty directory.
     *
     * @param $dirName
     *
     * @return Madzipper
     */
    public function addEmptyDir($dirName)
    {
        $this->repository->addEmptyDir($dirName);

        return $this;
    }

    /**
     * Add a file to the zip using its contents.
     *
     * @param $filename string The name of the file to create
     * @param $content string The file contents
     *
     * @return $this Madzipper instance
     */
    public function addString($filename, $content)
    {
        $this->addFromString($filename, $content);

        return $this;
    }

    /**
     * Gets the status of the zip.
     *
     * @return int The status of the internal zip file
     */
    public function getStatus()
    {
        return $this->repository->getStatus();
    }

    /**
     * Remove a file or array of files and folders from the zip archive.
     *
     * @param $fileToRemove array|string The path/array to the files in the zip
     *
     * @return $this Madzipper instance
     */
    public function remove($fileToRemove)
    {
        if (is_array($fileToRemove)) {
            $self = $this;
            $this->repository->each(function ($file) use ($fileToRemove, $self) {
                if (Str::startsWith($file, $fileToRemove)) {
                    $self->getRepository()->removeFile($file);
                }
            });
        } else {
            $this->repository->removeFile($fileToRemove);
        }

        return $this;
    }

    /**
     * Returns the path of the current zip file if there is one.
     *
     * @return string The path to the file
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Sets the password to be used for decompressing.
     *
     * @param $password
     *
     * @return bool
     */
    public function usePassword($password)
    {
        return $this->repository->usePassword($password);
    }

    /**
     * Closes the zip file and frees all handles.
     */
    public function close()
    {
        if (null !== $this->repository) {
            $this->repository->close();
        }
        $this->filePath = '';
    }

    /**
     * Sets the internal folder to the given path.<br/>
     * Useful for extracting only a segment of a zip file.
     *
     * @param $path
     *
     * @return $this
     */
    public function folder($path)
    {
        $this->currentFolder = $path;

        return $this;
    }

    /**
     * Resets the internal folder to the root of the zip file.
     *
     * @return $this
     */
    public function home()
    {
        $this->currentFolder = '';

        return $this;
    }

    /**
     * Deletes the archive file.
     */
    public function delete()
    {
        if (null !== $this->repository) {
            $this->repository->close();
        }

        $this->file->delete($this->filePath);
        $this->filePath = '';
    }

    /**
     * Get the type of the Archive.
     *
     * @return string
     */
    public function getArchiveType()
    {
        return get_class($this->repository);
    }

    /**
     * Get the current internal folder pointer.
     *
     * @return string
     */
    public function getCurrentFolderPath()
    {
        return $this->currentFolder;
    }

    /**
     * Checks if a file is present in the archive.
     *
     * @param $fileInArchive
     *
     * @return bool
     */
    public function contains($fileInArchive)
    {
        return $this->repository->fileExists($fileInArchive);
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return Filesystem
     */
    public function getFileHandler()
    {
        return $this->file;
    }

    /**
     * Gets the path to the internal folder.
     *
     * @return string
     */
    public function getInternalPath()
    {
        return empty($this->currentFolder) ? '' : $this->currentFolder.'/';
    }

    /**
     * List all files that are within the archive.
     *
     * @param string|null $regexFilter regular expression to filter returned files/folders. See @link http://php.net/manual/en/reference.pcre.pattern.syntax.php
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function listFiles($regexFilter = null)
    {
        $filesList = [];
        if ($regexFilter) {
            $filter = function ($file) use (&$filesList, $regexFilter) {
                // push/pop an error handler here to to make sure no error/exception thrown if $expected is not a regex
                set_error_handler(function () {
                });
                $match = preg_match($regexFilter, $file);
                restore_error_handler();

                if ($match === 1) {
                    $filesList[] = $file;
                } elseif ($match === false) {
                    throw new \RuntimeException("regular expression match on '$file' failed with error. Please check if pattern is valid regular expression.");
                }
            };
        } else {
            $filter = function ($file) use (&$filesList) {
                $filesList[] = $file;
            };
        }
        $this->repository->each($filter);

        return $filesList;
    }

    private function getCurrentFolderWithTrailingSlash()
    {
        if (empty($this->currentFolder)) {
            return '';
        }

        $lastChar = mb_substr($this->currentFolder, -1);
        if ($lastChar !== '/' || $lastChar !== '\\') {
            return $this->currentFolder.'/';
        }

        return $this->currentFolder;
    }

    /**
     * @param $pathToZip
     *
     * @throws Exception
     *
     * @return bool
     */
    private function createArchiveFile($pathToZip)
    {
        if (! $this->file->exists($pathToZip)) {
            $dirname = dirname($pathToZip);
            if (! $this->file->exists($dirname) && ! $this->file->makeDirectory($dirname, 0755, true)) {
                throw new \RuntimeException('Failed to create folder');
            } elseif (! $this->file->isWritable($dirname)) {
                throw new Exception(sprintf('The path "%s" is not writeable', $pathToZip));
            }

            return true;
        }

        return false;
    }

    /**
     * @param $pathToDir
     */
    private function addDir($pathToDir)
    {
        // First go over the files in this directory and add them to the repository.
        foreach ($this->file->files($pathToDir) as $file) {
            $this->addFile($pathToDir.'/'.basename($file));
        }

        // Now let's visit the subdirectories and add them, too.
        foreach ($this->file->directories($pathToDir) as $dir) {
            $old_folder = $this->currentFolder;
            $this->currentFolder = empty($this->currentFolder) ? basename($dir) : $this->currentFolder.'/'.basename($dir);
            $this->addDir($pathToDir.'/'.basename($dir));
            $this->currentFolder = $old_folder;
        }
    }

    /**
     * Add the file to the zip.
     *
     * @param string $pathToAdd
     * @param string $fileName
     */
    private function addFile($pathToAdd, $fileName = null)
    {
        if (! $fileName) {
            $info = pathinfo($pathToAdd);
            $fileName = isset($info['extension']) ?
                $info['filename'].'.'.$info['extension'] : $info['filename'];
        }

        $this->repository->addFile($pathToAdd, $this->getInternalPath().$fileName);
    }

    /**
     * Add the file to the zip from content.
     *
     * @param $filename
     * @param $content
     */
    private function addFromString($filename, $content)
    {
        $this->repository->addFromString($this->getInternalPath().$filename, $content);
    }

    private function extractFilesInternal($path, callable $matchingMethod)
    {
        $self = $this;
        $this->repository->each(function ($file) use ($path, $matchingMethod, $self) {
            $currentPath = $self->getCurrentFolderWithTrailingSlash();
            if (! empty($currentPath) && ! Str::startsWith($file, $currentPath)) {
                return;
            }

            $filename = str_replace($self->getInternalPath(), '', $file);
            if ($matchingMethod($filename)) {
                $self->extractOneFileInternal($file, $path);
            }
        });
    }

    /**
     * @param $fileName
     * @param $path
     *
     * @throws \RuntimeException
     */
    private function extractOneFileInternal($file, $path)
    {
        $tmpPath = str_replace($this->getInternalPath(), '', $file);

        //Prevent Zip traversal attacks
        if (strpos($file, '../') !== false || strpos($file, '..\\') !== false) {
            throw new \RuntimeException('Special characters found within filenames');
        }
        // We need to create the directory first in case it doesn't exist
        $dir = pathinfo($path.DIRECTORY_SEPARATOR.$tmpPath, PATHINFO_DIRNAME);
        if (! $this->file->exists($dir) && ! $this->file->makeDirectory($dir, 0755, true, true)) {
            throw new \RuntimeException('Failed to create folders');
        }

        $toPath = $path.DIRECTORY_SEPARATOR.$tmpPath;
        $fileStream = $this->getRepository()->getFileStream($file);
        $this->getFileHandler()->put($toPath, $fileStream);
    }
}
