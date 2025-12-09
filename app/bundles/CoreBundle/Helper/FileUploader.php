<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    /** @var string[] */
    protected array $imageMimes = [
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/webp',
        'image/svg+xml',
    ];

    /** @var string[] */
    protected array $imageExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
    ];

    public function __construct(
        private FilePathResolver $filePathResolver,
        private Translator $translator,
    ) {
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
            } catch (FileException) {
                throw new FileUploadException($this->translator->trans('mautic.core.fileuploader.upload_error'));
            }
        } catch (FilePathException $e) {
            throw new FileUploadException($e->getMessage());
        }
    }

    /**
     * Verify that the file is an image.
     *
     * @throws FileUploadException
     */
    public function validateImage(File $file): void
    {
        // Check if the file is an image
        if (!in_array($file->getMimeType(), $this->getAllowedImageMimeTypes())) {
            throw new FileUploadException($this->translator->trans('mautic.core.fileuploader.unsupported_image', ['%types%' => implode(', ', $this->getAllowedImageExtensions())]));
        }
        // Also check the file extension
        $extension = strtolower(pathinfo($file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, $this->getAllowedImageExtensions())) {
            throw new FileUploadException($this->translator->trans('mautic.core.fileuploader.unsupported_image', ['%types%' => implode(', ', $this->getAllowedImageExtensions())]));
        }
    }

    /**
     * @param string $path
     */
    public function delete($path): void
    {
        $this->filePathResolver->delete($path);
    }

    /**
     * @return string[]
     */
    public function getAllowedImageMimeTypes(): array
    {
        return $this->imageMimes;
    }

    /**
     * @return string[]
     */
    public function getAllowedImageExtensions(): array
    {
        return $this->imageExtensions;
    }

    /**
     * Allow compiler passes to add additional image MIME types.
     */
    public function addAllowedImageMimeType(string $mimeType): self
    {
        $this->imageMimes[] = $mimeType;

        return $this;
    }

    /**
     * Allow compiler passes to add additional image extensions.
     */
    public function addAllowedImageExtension(string $extension): self
    {
        $this->imageExtensions[] = $extension;

        return $this;
    }
}
