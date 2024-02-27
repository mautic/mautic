<?php

namespace Mautic\LeadBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\HttpFoundation\RequestStack;

class SerializerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event'  => Events::POST_SERIALIZE,
                'method' => 'changeEmptyArraysToObject',
            ],
        ];
    }

    public function changeEmptyArraysToObject(ObjectEvent $event): void
    {
        $request  = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $object = $event->getObject();

        if (!$object instanceof LeadField) {
            return;
        }

        if (empty($object->getProperties())) {
            // fixing array/object discrepancy for empty properties
            $event->getContext()->getVisitor()->setData('properties', new \ArrayObject());
        }
    }
}
