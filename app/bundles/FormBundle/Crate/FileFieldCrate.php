<?php

namespace Mautic\FormBundle\Crate;

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileFieldCrate
{
    public function __construct(
        private UploadedFile $uploadedFile,
        private Field $field,
    ) {
    }

    public function getUploadedFile(): UploadedFile
    {
        return $this->uploadedFile;
    }

    public function getField(): Field
    {
        return $this->field;
    }
}
