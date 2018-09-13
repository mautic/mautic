<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request;

/**
 * Class RequestDAO
 */
class RequestDAO
{
    /**
     * @var int
     */
    private $syncIteration;

    /**
     * @var bool
     */
    private $isFirstTimeSync;

    /**
     * @var string
     */
    private $syncToIntegration;

    /**
     * @var ObjectDAO[]
     */
    private $objects = [];

    /**
     * RequestDAO constructor.
     *
     * @param int    $syncIteration
     * @param bool   $isFirstTimeSync
     * @param string $syncToIntegration
     */
    public function __construct($syncIteration, $isFirstTimeSync, string $syncToIntegration)
    {
        $this->syncIteration     = (int) $syncIteration;
        $this->isFirstTimeSync   = $isFirstTimeSync;
        $this->syncToIntegration = $syncToIntegration;
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
     * @return int
     */
    public function getSyncIteration(): int
    {
        return $this->syncIteration;
    }

    /**
     * @return bool
     */
    public function isFirstTimeSync(): bool
    {
        return $this->isFirstTimeSync;
    }

    /**
     * The integration that will be synced to
     *
     * @return string
     */
    public function getSyncToIntegration(): string
    {
        return $this->syncToIntegration;
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
