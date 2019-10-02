<?php

declare(strict_types=1);

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
    private $oldObjectId;

    /**
     * @var string
     */
    private $oldObjectName;

    /**
     * @var string
     */
    private $newObjectName;

    /**
     * @var mixed
     */
    private $newObjectId;

    /**
     * @param string $integration
     * @param string $oldObjectName
     * @param mixed  $oldObjectId
     * @param string $newObjectName
     * @param mixed  $newObjectId
     */
    public function __construct(string $integration, string $oldObjectName, $oldObjectId, string $newObjectName, $newObjectId)
    {
        $this->integration   = $integration;
        $this->oldObjectName = $oldObjectName;
        $this->oldObjectId   = $oldObjectId;
        $this->newObjectName = $newObjectName;
        $this->newObjectId   = $newObjectId;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @return string
     */
    public function getOldObjectName(): string
    {
        return $this->oldObjectName;
    }

    /**
     * @return mixed
     */
    public function getOldObjectId()
    {
        return $this->oldObjectId;
    }

    /**
     * @return string
     */
    public function getNewObjectName(): string
    {
        return $this->newObjectName;
    }

    /**
     * @return mixed
     */
    public function getNewObjectId()
    {
        return $this->newObjectId;
    }
}
