<?php

/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FilePathResolver
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var InputHelper
     */
    private $inputHelper;

    public function __construct(Filesystem $filesystem, InputHelper $inputHelper)
    {
        $this->filesystem  = $filesystem;
        $this->inputHelper = $inputHelper;
    }

    /**
     * @param string       $uploadDir
     * @param UploadedFile $file
     *
     * @return string
     *
     * @throws FilePathException
     */
    public function getUniqueFileName($uploadDir, UploadedFile $file)
    {
        $inputHelper       = $this->inputHelper;
        $fullName          = $file->getClientOriginalName();
        $fullNameSanitized = $inputHelper::filename($fullName);

        $ext = $this->getFileExtension($file);

        $baseFileName = pathinfo($fullNameSanitized, PATHINFO_FILENAME);

        $name = $baseFileName;

        $filePath = $this->getFilePath($uploadDir, $baseFileName, $ext);

        $i = 1;
        while ($this->filesystem->exists($filePath)) {
            $name     = $baseFileName.'-'.$i;
            $filePath = $this->getFilePath($uploadDir, $name, $ext);
            ++$i;

            if ($i > 100) {
                throw new FilePathException('Could not generate path');
            }
        }

        return $name.$ext;
    }

    /**
     * @param string $directory
     *
     * @throws FilePathException
     */
    public function createDirectory($directory)
    {
        if ($this->filesystem->exists($directory)) {
            return;
        }
        try {
            $this->filesystem->mkdir($directory);
        } catch (IOException $e) {
            throw new FilePathException('Could not create directory');
        }
    }

    private function getFilePath($uploadDir, $fileName, $ext)
    {
        return $uploadDir.DIRECTORY_SEPARATOR.$fileName.$ext;
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    private function getFileExtension(UploadedFile $file)
    {
        $ext = $file->getClientOriginalExtension();
        $ext = ($ext === '' ? '' : '.').$ext;

        return $ext;
    }

    /**
     * @param string $file
     */
    public function deleteFile($file)
    {
        try {
            $this->filesystem->remove($file);
        } catch (IOException $e) {
        }
    }
}
