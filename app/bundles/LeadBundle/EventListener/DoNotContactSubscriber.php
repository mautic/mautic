<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\DoNotContactAddEvent;
use Mautic\LeadBundle\Event\DoNotContactRemoveEvent;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class DoNotContactSubscriber
{
    public function __construct(
        private DoNotContact $doNotContact,
    ) {
    }

    #[AsEventListener(
        event: DoNotContactRemoveEvent::class,
        priority: 0
    )]
    public function removeDncForLead(DoNotContactRemoveEvent $doNotContactRemoveEvent): void
    {
        $this->doNotContact->removeDncForContact(
            $doNotContactRemoveEvent->getLead()->getId(),
            $doNotContactRemoveEvent->getChannel(),
            $doNotContactRemoveEvent->getPersist()
        );
    }

    #[AsEventListener(
        event: DoNotContactAddEvent::class,
        priority: 0
    )]
    public function addDncForLead(DoNotContactAddEvent $doNotContactAddEvent): void
    {
        if (empty($doNotContactAddEvent->getLead()->getId())) {
            $this->doNotContact->createDncRecord(
                $doNotContactAddEvent->getLead(),
                $doNotContactAddEvent->getChannel(),
                $doNotContactAddEvent->getReason(),
                $doNotContactAddEvent->getComments()
            );
        } else {
            $this->doNotContact->addDncForContact(
                $doNotContactAddEvent->getLead(),
                $doNotContactAddEvent->getChannel(),
                $doNotContactAddEvent->getReason(),
                $doNotContactAddEvent->getComments(),
                $doNotContactAddEvent->isPersist(),
                $doNotContactAddEvent->isCheckCurrentStatus(),
                $doNotContactAddEvent->isOverride()
            );
        }
    }
}
