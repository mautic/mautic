<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface UpsertInterface
{
    public const ROWS_AFFECTED_ON_INSERT = 1;
    public const ROWS_AFFECTED_ON_UPDATE = 2;

    public function hasBeenInserted(): bool;

    public function hasBeenUpdated(): bool;

    public function setHasBeenUpdated(bool $hasBeenUpdated): void;

    public function setHasBeenInserted(bool $hasBeenInserted): void;
}
