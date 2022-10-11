<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

trait UpsertTrait
{
    private bool $hasBeenInserted = false;
    private bool $hasBeenUpdated  = false;

    public function hasBeenInserted(): bool
    {
        return $this->hasBeenInserted;
    }

    public function hasBeenUpdated(): bool
    {
        return $this->hasBeenUpdated;
    }

    public function setHasBeenInserted(bool $hasBeenInserted): void
    {
        $this->hasBeenInserted = $hasBeenInserted;
    }

    public function setHasBeenUpdated(bool $hasBeenUpdated): void
    {
        $this->hasBeenUpdated = $hasBeenUpdated;
    }
}
