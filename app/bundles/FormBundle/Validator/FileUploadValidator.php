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

use Mautic\FormBundle\Exception\FileUploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

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
            $this->checkExtension($file->getExtension(), $allowedExtensions);
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
            $error = $this->translator->trans('mautic.asset.asset.error.file.extension', [
                '%fileExtension%' => $extension,
                '%extensions%'    => implode(', ', $allowedExtensions),
            ], 'validators');

            throw new FileUploadException($error);
        }
    }

    /**
     * @param UploadedFile $file
     * @param string       $maxUploadSize
     *
     * @throws FileUploadException
     */
    public function checkFileSize(UploadedFile $file, $maxUploadSize)
    {
        if (!$maxUploadSize) {
            return;
        }

        $fileConstraints = ['maxSize' => $maxUploadSize];

        $validator = Validation::createValidator();
        $errors    = $validator->validate($file, new File($fileConstraints));

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        if ($errorMessages) {
            $message = implode('<br />', $errorMessages);
            throw new FileUploadException($message);
        }
    }
}
