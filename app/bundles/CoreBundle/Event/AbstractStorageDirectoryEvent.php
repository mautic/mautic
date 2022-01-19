<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

abstract class AbstractStorageDirectoryEvent extends AbstractStorageEvent implements StorageDirectoryEventInterface
{
    private ?array $files           = null;
    private ?array $rootDirectories = null;

    public function existsInStorage(): ?bool
    {
        return null !== $this->files;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    /**
     * Return list of files from directory.
     */
    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    /**
     * Return just filenames from directory content.
     */
    public function getFileNames(): array
    {
        return array_map(function ($file) {
            return pathinfo($file, PATHINFO_BASENAME);
        }, $this->files);
    }

    /**
     * Return one-level directories from directory. Require for themes.
     */
    public function getRootDirectories(): ?array
    {
        return $this->rootDirectories;
    }

    public function setRootDirectories(?array $rootDirectories): void
    {
        $this->rootDirectories = $rootDirectories;
    }

    private function getRelativePath(string $absolutePathToFile): string
    {
        $isDirectory        = empty(pathinfo($absolutePathToFile, PATHINFO_EXTENSION));
        $absolutePathToFile = $isDirectory ? $absolutePathToFile.'/x.ext' : $absolutePathToFile;
        $pathInfo           = pathinfo($absolutePathToFile);
        $absolutePath       = realpath($pathInfo['dirname']) ?: $pathInfo['dirname'];
        $fileName           = $pathInfo['basename'];
        $rootPath           = $_SERVER['DOCUMENT_ROOT'];
        $fileSystem         = new Filesystem();

        return $fileSystem->makePathRelative($absolutePath, $rootPath);
    }
}
