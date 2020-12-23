<?php

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

interface UuidInterface
{
    public function getUuid(): string;

    public function setUuid(string $uuid): void;
}
