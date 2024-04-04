<?php

namespace Mautic\FormBundle\Crate;

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileCrate implements \Iterator
{
    /**
     * @var array|FileFieldCrate[]
     */
    private array $fileFieldCrate = [];

    private int $position = 0;

    public function addFile(UploadedFile $file, Field $field): void
    {
        $this->fileFieldCrate[] = new FileFieldCrate($file, $field);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->fileFieldCrate[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->fileFieldCrate[$this->position]);
    }
}
