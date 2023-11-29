<?php

namespace Mautic\CampaignBundle\Entity\Result;

class CountResult
{
    private int $count;

    private int $minId;

    private int $maxId;

    public function __construct($count, $minId, $maxId)
    {
        $this->count = (int) $count;
        $this->minId = (int) $minId;
        $this->maxId = (int) $maxId;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getMinId()
    {
        return $this->minId;
    }

    /**
     * @return int
     */
    public function getMaxId()
    {
        return $this->maxId;
    }
}
