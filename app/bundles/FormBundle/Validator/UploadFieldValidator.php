<?php

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
    public function __construct(
        private FileUploadValidator $fileUploadValidator,
    ) {
    }

    /**
     * @return UploadedFile
     *
     * @throws FileValidationException
     * @throws NoFileGivenException
     */
    public function processFileValidation(Field $field, Request $request)
    {
        $files = $request->files->get('mauticform');

        if (!$files || !array_key_exists($field->getAlias(), $files) || !$files[$field->getAlias()] instanceof UploadedFile) {
            throw new NoFileGivenException();
        }

        $file = $files[$field->getAlias()];

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
