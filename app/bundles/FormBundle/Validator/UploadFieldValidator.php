<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Validator;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Exception\FileValidationException;
use Mautic\FormBundle\Exception\NoFileGivenException;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class UploadFieldValidator
{
    /**
     * @var FileUploadValidator
     */
    private $fileUploadValidator;

    public function __construct(FileUploadValidator $fileUploadValidator)
    {
        $this->fileUploadValidator = $fileUploadValidator;
    }

    /**
     * @param Field   $field
     * @param Request $request
     *
     * @return UploadedFile
     *
     * @throws FileValidationException
     * @throws NoFileGivenException
     */
    public function processFileValidation(Field $field, Request $request)
    {
        $files = $request->files->get('mauticform');

        if (!$files || !array_key_exists($field->getAlias(), $files)) {
            throw new NoFileGivenException();
        }

        /** @var UploadedFile $file */
        $file = $files[$field->getAlias()];

        if (!$file instanceof UploadedFile) {
            throw new NoFileGivenException();
        }

        $properties = $field->getProperties();

        $maxUploadSize     = $properties[FormFieldFileType::PROPERTY_ALLOWED_FILE_SIZE];
        $allowedExtensions = $properties[FormFieldFileType::PROPERTY_ALLOWED_FILE_EXTENSIONS];

        try {
            $this->fileUploadValidator->validate($file->getSize(), $file->getClientOriginalExtension(), $maxUploadSize, $allowedExtensions, 'mautic.form.submission.error.file.extension', 'mautic.form.submission.error.file.size');

            return $file;
        } catch (FileInvalidException $e) {
            throw new FileValidationException($e->getMessage());
        }
    }
}
