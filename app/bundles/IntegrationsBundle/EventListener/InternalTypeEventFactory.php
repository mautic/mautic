<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\IntegrationsBundle\Event\InternalCompanyEvent;
use Mautic\IntegrationsBundle\Event\InternalContactEvent;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

class InternalTypeEventFactory
{
    /**
     * @var string
     */
    private $eventName;

    /**
     * @var Event
     */
    private $event;

    /**
     * @throws InvalidValueException
     */
    public static function create(string $objectType, string $integrationName, object $object): InternalTypeEventFactory
    {
        $internalTypeFactory = new self();

        switch ($objectType) {
            case Lead::class:
                $internalTypeFactory->eventName = IntegrationEvents::INTEGRATION_BEFORE_CONTACT_FIELD_CHANGES;
                $internalTypeFactory->event     = new InternalContactEvent($integrationName, $object);
                break;
            case Company::class:
                $internalTypeFactory->eventName = IntegrationEvents::INTEGRATION_BEFORE_COMPANY_FIELD_CHANGES;
                $internalTypeFactory->event     = new InternalCompanyEvent($integrationName, $object);
                break;
            default:
                throw new InvalidValueException('An object type should be specified. None matches.');
        }

        return $internalTypeFactory;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}
