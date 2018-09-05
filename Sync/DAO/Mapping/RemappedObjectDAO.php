<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping;

class RemappedObjectDAO
{
    /**
     * @var string
     */
    private $integration;
    /**
     * @var mixed
     */
    private $objectId;

    /**
     * @var string
     */
    private $oldObjectName;

    /**
     * @var string
     */
    private $newObjectName;

    /**
     * RemappedObjectDAO constructor.
     *
     * @param        $integration
     * @param        $objectId
     * @param string $oldObjectName
     * @param string $newObjectName
     */
    public function __construct(string $integration, $objectId, string $oldObjectName, string $newObjectName)
    {
        $this->integration   = $integration;
        $this->objectId      = $objectId;
        $this->oldObjectName = $oldObjectName;
        $this->newObjectName = $newObjectName;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getOldObjectName(): string
    {
        return $this->oldObjectName;
    }

    /**
     * @return string
     */
    public function getNewObjectName(): string
    {
        return $this->newObjectName;
    }
}