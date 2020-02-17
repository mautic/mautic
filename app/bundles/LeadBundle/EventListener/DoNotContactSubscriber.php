<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\DoNotContactAddEvent;
use Mautic\LeadBundle\Event\DoNotContactRemoveEvent;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DoNotContactSubscriber implements EventSubscriberInterface
{
    private $doNotContact;

    public function __construct(DoNotContact $doNotContact)
    {
        $this->doNotContact = $doNotContact;
    }

    public static function getSubscribedEvents()
    {
        return [
            DoNotContactAddEvent::class    => ['addDncForLead', 0],
            DoNotContactRemoveEvent::class => ['removeDncForLead', 0],
        ];
    }

    /**
     * @param $lead
     * @param $channel
     * @param bool $persist
     * @param null $reason
     */
    public function removeDncForLead(DoNotContactRemoveEvent $doNotContactRemoveEvent)
    {
        $this->doNotContact->removeDncForContact(
            $doNotContactRemoveEvent->getLead()->getId(),
            $doNotContactRemoveEvent->getChannel(),
            $doNotContactRemoveEvent->getPersist()
        );
    }

    public function addDncForLead(DoNotContactAddEvent $doNotContactAddEvent)
    {
        $this->doNotContact->addDncForContact(
            $doNotContactAddEvent->getLead()->getId(),
            $doNotContactAddEvent->getChannel(),
            $doNotContactAddEvent->getReason(),
            $doNotContactAddEvent->getComments(),
            $doNotContactAddEvent->isPersist(),
            $doNotContactAddEvent->isCheckCurrentStatus(),
            $doNotContactAddEvent->isOverride()
        );
    }
}
