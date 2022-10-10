<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface UpsertInterface
{
    public function wasInserted(): bool;

    public function wasUpdated(): bool;

    public function setWasUpdated(bool $wasUpdated): void;

    public function setWasInserted(bool $wasInserted): void;
}
