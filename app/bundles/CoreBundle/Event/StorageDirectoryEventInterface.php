<?php

namespace Mautic\CoreBundle\Event;

interface StorageDirectoryEventInterface
{
    public function getFiles(): ?array;

    public function setFiles(array $files): void;

    public function getRootDirectories(): ?array;

    public function setRootDirectories(array $files): void;
}
