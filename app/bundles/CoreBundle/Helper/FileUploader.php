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
use Mautic\CoreBundle\Exception\FileUploadException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    /**
     * @var FilePathResolver
     */
    private $filePathResolver;

    public function __construct(FilePathResolver $filePathResolver)
    {
        $this->filePathResolver = $filePathResolver;
    }

    /**
     * @param string       $uploadDir
     * @param UploadedFile $file
     *
     * @return string
     *
     * @throws FileUploadException
     */
    public function upload($uploadDir, UploadedFile $file)
    {
        try {
            $fileName = $this->filePathResolver->getUniqueFileName($uploadDir, $file);
            $this->filePathResolver->createDirectory($uploadDir);

            try {
                $file->move($uploadDir, $fileName);

                return $fileName;
            } catch (FileException $e) {
                throw new FileUploadException('Could not upload file');
            }
        } catch (FilePathException $e) {
            throw new FileUploadException($e->getMessage());
        }
    }

    /**
     * @param string $file
     */
    public function deleteFile($file)
    {
        $this->filePathResolver->deleteFile($file);
    }
}
