<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DynamicContentCustomSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['onContactsFilterEvaluate', 0]
        ];
    }

    public function onContactsFilterEvaluate(ContactFiltersEvaluateEvent $event): void
    {
        $contact = $event->getContact();
        $filters = $event->getFilters();

        // logic

        $flag = true;
        $event->setIsMatched($flag);
        $event->setIsEvaluated($flag);
    }
}