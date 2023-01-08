<?php

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
     * @param string $uploadDir
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
     * @param string $path
     */
    public function delete($path)
    {
        $this->filePathResolver->delete($path);
    }
}
