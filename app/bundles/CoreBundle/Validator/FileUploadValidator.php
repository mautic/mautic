<?php

namespace Mautic\CoreBundle\Validator;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Helper\FileHelper;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;

class FileUploadValidator
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    /**
     * @param int    $fileSize          In bytes
     * @param string $fileExtension
     * @param int    $maxUploadSize     In bytes
     * @param string $extensionErrorMsg
     * @param string $sizeErrorMsg
     *
     * @throws FileInvalidException
     */
    public function validate($fileSize, $fileExtension, $maxUploadSize, array $allowedExtensions, $extensionErrorMsg, $sizeErrorMsg): void
    {
        $errors = [];

        try {
            $this->checkExtension($fileExtension, $allowedExtensions, $extensionErrorMsg);
        } catch (FileInvalidException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->checkFileSize($fileSize, $maxUploadSize, $sizeErrorMsg);
        } catch (FileInvalidException $e) {
            $errors[] = $e->getMessage();
        }

        if ($errors) {
            $message = implode('<br />', $errors);
            throw new FileInvalidException($message);
        }
    }

    /**
     * @param string $extension
     * @param string $extensionErrorMsg
     *
     * @throws FileInvalidException
     */
    public function checkExtension($extension, array $allowedExtensions, $extensionErrorMsg = 'mautic.asset.asset.error.file.extension'): void
    {
        $extension         = strtolower($extension);
        $allowedExtensions = array_map(strtolower(...), $allowedExtensions);

        if (!in_array($extension, $allowedExtensions, true)) {
            $this->throwException($extensionErrorMsg, [
                '%fileExtension%' => $extension,
                '%extensions%'    => implode(', ', $allowedExtensions),
            ]);
        }
    }

    /**
     * @param int    $fileSize
     * @param string $maxUploadSizeMB Max file size in MB
     * @param string $sizeErrorMsg
     *
     * @throws FileInvalidException
     */
    public function checkFileSize($fileSize, $maxUploadSizeMB, $sizeErrorMsg = 'mautic.asset.asset.error.file.size'): void
    {
        if (!$maxUploadSizeMB) {
            return;
        }

        $maxUploadSize = FileHelper::convertMegabytesToBytes($maxUploadSizeMB);

        if ($fileSize > $maxUploadSize) {
            $this->throwException($sizeErrorMsg, [
                '%fileSize%' => FileHelper::convertBytesToMegabytes($fileSize),
                '%maxSize%'  => FileHelper::convertBytesToMegabytes($maxUploadSize),
            ]);
        }
    }

    /**
     * @param string[] $allowedExtensions
     *
     * @throws FileInvalidException
     */
    public function checkMimeType(string $mimeType, array $allowedExtensions, string $messageId = 'mautic.asset.asset.error.invalid.mimetype'): void
    {
        $allowedExtensions = array_map(strtolower(...), $allowedExtensions);
        $extensions        = $this->getExtensionsByMimeType($mimeType);

        foreach ($extensions as $extension) {
            try {
                $this->checkExtension($extension, $allowedExtensions);

                return;
            } catch (FileInvalidException) {
                $e = $this->buildException($messageId, [
                    '%fileMimetype%'      => $mimeType,
                    '%extensions%'        => implode(', ', $extensions),
                    '%allowedExtensions%' => implode(', ', $allowedExtensions),
                ]);
            }
        }

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @param string[] $mimeTypes
     *
     * @throws FileInvalidException
     */
    public function checkMimeTypesMatchExtension(array $mimeTypes, string $extension, string $messageId = 'mautic.asset.asset.error.mimetype.not.match.extension'): void
    {
        $extension  = strtolower($extension);

        foreach ($mimeTypes as $mimeType) {
            $mimeType = strtolower((string) $mimeType);

            if (!$this->isMimeTypeAllowed($mimeType, $extension)) {
                $this->throwException($messageId, [
                    '%fileMimetype%'  => $mimeType,
                    '%fileExtension%' => $extension,
                ]);
            }
        }
    }

    private function isMimeTypeAllowed(string $mimeType, string $extension): bool
    {
        $extensions = $this->getExtensionsByMimeType($mimeType);

        return !$extensions || in_array($extension, $extensions, true);
    }

    /**
     * @return string[]
     */
    private function getExtensionsByMimeType(string $mimeType): array
    {
        $extensions = ['php', 'php3', 'php4', 'php5', 'phps'];
        $mimeTypes  = new MimeTypes([
            'text/php'                       => $extensions,
            'text/x-php'                     => $extensions,
            'application/php'                => $extensions,
            'application/x-httpd-php-source' => $extensions,
        ]);

        return $mimeTypes->getExtensions($mimeType);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function buildException(string $messageId, array $parameters): FileInvalidException
    {
        $exception = new FileInvalidException($this->translator->trans($messageId, $parameters, 'validators'));
        $exception->setMessageId($messageId);
        $exception->setParameters($parameters);

        return $exception;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws FileInvalidException
     */
    private function throwException(string $messageId, array $parameters): void
    {
        throw $this->buildException($messageId, $parameters);
    }
}
