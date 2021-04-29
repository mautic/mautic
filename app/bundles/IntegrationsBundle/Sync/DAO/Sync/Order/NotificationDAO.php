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

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

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

    public function getMauticObjectId(): int
    {
        return (int) $this->objectChangeDAO->getMappedObjectId();
    }

    public function getIntegration(): string
    {
        return $this->objectChangeDAO->getIntegration();
    }

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

    public function getMessage(): string
    {
        return $this->message;
    }
}
