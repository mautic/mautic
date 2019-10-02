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

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order;

class NotificationDAO
{
    /**
     * @var ObjectChangeDAO
     */
    private $objectChangeDAO;

    /**
     * @var string
     */
    private $message;

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @param string          $message
     */
    public function __construct(ObjectChangeDAO $objectChangeDAO, string $message)
    {
        $this->objectChangeDAO = $objectChangeDAO;
        $this->message         = $message;
    }

    /**
     * @return ObjectChangeDAO
     */
    public function getMauticObject(): string
    {
        return $this->objectChangeDAO->getMappedObject();
    }

    /**
     * @return int
     */
    public function getMauticObjectId(): int
    {
        return $this->objectChangeDAO->getMappedObjectId();
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->objectChangeDAO->getIntegration();
    }

    /**
     * @return string
     */
    public function getIntegrationObject(): string
    {
        return $this->objectChangeDAO->getObject();
    }

    /**
     * @return mixed
     */
    public function getIntegrationObjectId()
    {
        return $this->objectChangeDAO->getObjectId();
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
