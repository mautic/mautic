<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Tag;
use Symfony\Contracts\EventDispatcher\Event;

final class TagMergeEvent extends Event
{
    public function __construct(
        private readonly Tag $primaryTag,
        private readonly Tag $secondaryTag,
    ) {
    }

    public function getPrimaryTag(): Tag
    {
        return $this->primaryTag;
    }

    public function getSecondaryTag(): Tag
    {
        return $this->secondaryTag;
    }
}
