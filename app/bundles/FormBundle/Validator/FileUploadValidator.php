<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Validator;

use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\FormBundle\Exception\FileUploadException;
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
     * @param UploadedFile $file
     * @param int          $maxUploadSize
     * @param array        $allowedExtensions
     *
     * @throws FileUploadException
     */
    public function validate(UploadedFile $file, $maxUploadSize, array $allowedExtensions)
    {
        $errors = [];

        try {
            $this->checkExtension($file->getClientOriginalExtension(), $allowedExtensions);
        } catch (FileUploadException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->checkFileSize($file, $maxUploadSize);
        } catch (FileUploadException $e) {
            $errors[] = $e->getMessage();
        }

        if ($errors) {
            $message = implode('<br />', $errors);
            throw new FileUploadException($message);
        }
    }

    /**
     * @param string $extension
     * @param array  $allowedExtensions
     *
     * @throws FileUploadException
     */
    public function checkExtension($extension, array $allowedExtensions)
    {
        if (!in_array(strtolower($extension), array_map('strtolower', $allowedExtensions), true)) {
            $error = $this->translator->trans('mautic.form.submission.error.file.extension', [
                '%fileExtension%' => $extension,
                '%extensions%'    => implode(', ', $allowedExtensions),
            ], 'validators');

            throw new FileUploadException($error);
        }
    }

    /**
     * @param UploadedFile $file
     * @param string       $maxUploadSizeMB Max file size in MB
     *
     * @throws FileUploadException
     */
    public function checkFileSize(UploadedFile $file, $maxUploadSizeMB)
    {
        if (!$maxUploadSizeMB) {
            return;
        }

        $maxUploadSize = FileHelper::convertMegabytesToBytes($maxUploadSizeMB);

        if ($file->getSize() > $maxUploadSize) {
            $message = $this->translator->trans('mautic.form.submission.error.file.size', [
                '%fileSize%' => FileHelper::convertBytesToMegabytes($file->getSize()),
                '%maxSize%'  => FileHelper::convertBytesToMegabytes($maxUploadSize),
            ], 'validators');

            throw new FileUploadException($message);
        }
    }
}
