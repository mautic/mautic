<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface UuidInterface
{
    public function getUuid(): ?string;

    public function setUuid(string $uuid): void;
}
