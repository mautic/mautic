<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity\Result;

final readonly class CountResult
{
    public function __construct(
        private int $count,
        private int $minId,
        private int $maxId
    )
    {
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getMinId(): int
    {
        return $this->minId;
    }

    public function getMaxId(): int
    {
        return $this->maxId;
    }
}
