<?php

namespace Mautic\CoreBundle\Event;

interface StorageFileEventInterface
{
    public function getContents(): ?string;

    public function setContents(string $contents): void;

    public function getUrl(): ?string;

    public function setUrl(string $url): void;
}
