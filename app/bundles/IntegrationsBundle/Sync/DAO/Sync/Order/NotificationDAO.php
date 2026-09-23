<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

final readonly class NotificationDAO
{
    public function __construct(
        private ObjectChangeDAO $objectChangeDAO,
        private string $message,
    ) {
    }

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
