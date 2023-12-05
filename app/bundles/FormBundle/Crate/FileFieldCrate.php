<?php

namespace Mautic\FormBundle\Crate;

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileFieldCrate
{
    private \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile;

    private \Mautic\FormBundle\Entity\Field $field;

    public function __construct(UploadedFile $uploadedFile, Field $field)
    {
        $this->uploadedFile = $uploadedFile;
        $this->field        = $field;
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
