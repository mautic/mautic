<?php

namespace Mautic\CampaignBundle\Entity\Result;

class CountResult
{
    private readonly int $count;

    private readonly int $minId;

    private readonly int $maxId;

    public function __construct($count, $minId, $maxId)
    {
        $this->count = (int) $count;
        $this->minId = (int) $minId;
        $this->maxId = (int) $maxId;
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
