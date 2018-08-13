<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request;

/**
 * Class RequestDAO
 */
class RequestDAO
{
    /**
     * @var int|null
     */
    private $fromTimestamp = null;

    /**
     * @var int|null
     */
    private $toTimestamp = null;

    /**
     * @var ObjectDAO[]
     */
    private $objects = [];

    /**
     * RequestDAO constructor.
     *
     * @param int|null $fromTimestamp
     * @param int|null $toTimestamp
     */
    public function __construct(int $fromTimestamp = null, int $toTimestamp = null)
    {
        $this->fromTimestamp = $fromTimestamp;
        $this->toTimestamp   = $toTimestamp;
    }

    /**
     * @return int|null
     */
    public function getFromTimestamp(): ?int
    {
        return $this->fromTimestamp;
    }

    /**
     * @return int|null
     */
    public function getToTimestamp(): ?int
    {
        return $this->toTimestamp;
    }

    /**
     * @param ObjectDAO $objectDAO
     *
     * @return self
     */
    public function addObject(ObjectDAO $objectDAO)
    {
        $this->objects[] = $objectDAO;

        return $this;
    }

    /**
     * @return ObjectDAO[]
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    /**
     * Returns true if there are objects to sync
     *
     * @return bool
     */
    public function shouldSync()
    {
        return !empty($this->objects);
    }
}
