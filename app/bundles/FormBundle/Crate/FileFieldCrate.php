<?php

namespace Mautic\FormBundle\Crate;

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileFieldCrate
{
    public function __construct(
        private readonly UploadedFile $uploadedFile,
        private readonly Field $field,
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
