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

    /**
     * @return UploadedFile
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }
}
