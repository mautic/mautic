<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order;


class NotificationDAO
{
    /**
     * @var ObjectChangeDAO
     */
    private $objectChangeDOO;

    /**
     * @var string
     */
    private $message;

    /**
     * NotificationDAO constructor.
     *
     * @param ObjectChangeDAO $objectChangeDOO
     * @param string          $message
     */
    public function __construct(ObjectChangeDAO $objectChangeDOO, string $message)
    {
        $this->objectChangeDOO = $objectChangeDOO;
        $this->message         = $message;
    }

    /**
     * @return ObjectChangeDAO
     */
    public function getMauticObject(): string
    {
        return $this->objectChangeDOO->getMappedObject();
    }

    /**
     * @return int
     */
    public function getMauticObjectId(): int
    {
        return $this->objectChangeDOO->getMappedObjectId();
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->objectChangeDOO->getIntegration();
    }

    /**
     * @return string
     */
    public function getIntegrationObject(): string
    {
        return $this->objectChangeDOO->getObject();
    }

    /**
     * @return mixed
     */
    public function getIntegrationObjectId()
    {
        return $this->objectChangeDOO->getObjectId();
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}