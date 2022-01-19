<?php

namespace Mautic\CoreBundle\Event;

interface StorageEventInterface
{
    public function getAbsolutePath(): string;

    public function setValid(bool $valid): void;

    public function isValid(): ?bool;

    public function setRemoved(bool $fileValid): void;

    public function isRemoved(): ?bool;
}
