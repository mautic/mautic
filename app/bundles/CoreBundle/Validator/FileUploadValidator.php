<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Validator;

use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\CoreBundle\Exception\FileUploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Translation\TranslatorInterface;

class FileUploadValidator
{
    /**
     * @param TranslatorInterface $translator
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param int       $fileSize In bytes
     * @param string    $fileExtension
     * @param int       $maxUploadSize In bytes
     * @param array     $allowedExtensions
     * @param string    $extensionErrorMsg
     * @param string    $sizeErrorMsg
     *
     * @throws FileUploadException
     */
    public function validate($fileSize, $fileExtension, $maxUploadSize, array $allowedExtensions, $extensionErrorMsg, $sizeErrorMsg)
    {
        $errors = [];

        try {
            $this->checkExtension($fileExtension, $allowedExtensions, $extensionErrorMsg);
        } catch (FileUploadException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->checkFileSize($fileSize, $maxUploadSize, $sizeErrorMsg);
        } catch (FileUploadException $e) {
            $errors[] = $e->getMessage();
        }

        if ($errors) {
            $message = implode('<br />', $errors);
            throw new FileUploadException($message);
        }
    }

    /**
     * @param string    $extension
     * @param array     $allowedExtensions
     * @param string    $extensionErrorMsg
     *
     * @throws FileUploadException
     */
    public function checkExtension($extension, array $allowedExtensions, $extensionErrorMsg)
    {
        if (!in_array(strtolower($extension), array_map('strtolower', $allowedExtensions), true)) {
            $error = $this->translator->trans($extensionErrorMsg, [
                '%fileExtension%' => $extension,
                '%extensions%'    => implode(', ', $allowedExtensions),
            ], 'validators');

            throw new FileUploadException($error);
        }
    }

    /**
     * @param int       $fileSize
     * @param string    $maxUploadSizeMB Max file size in MB
     * @param string    $sizeErrorMsg
     *
     * @throws FileUploadException
     */
    public function checkFileSize($fileSize, $maxUploadSizeMB, $sizeErrorMsg)
    {
        if (!$maxUploadSizeMB) {
            return;
        }

        $maxUploadSize = FileHelper::convertMegabytesToBytes($maxUploadSizeMB);

        if ($fileSize > $maxUploadSize) {
            $message = $this->translator->trans($sizeErrorMsg, [
                '%fileSize%' => FileHelper::convertBytesToMegabytes($fileSize),
                '%maxSize%'  => FileHelper::convertBytesToMegabytes($maxUploadSize),
            ], 'validators');

            throw new FileUploadException($message);
        }
    }
}
