<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

trait UpsertTrait
{
    /*
     * This file has been added to the list of excluded files in phpstan.neon, since it is (currently) not being used in a entity.
     * When using this trait in an entity, make sure you
     * 1. Remove this comment
     * 2. Remove it from the list of excluded files in phpstan.neon
     */
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
