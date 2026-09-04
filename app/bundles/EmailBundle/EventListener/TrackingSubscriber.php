<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class TrackingSubscriber implements EventSubscriberInterface
{
    /**
     * TrackingSubscriber constructor.
     */
    public function __construct(
        private StatRepository $statRepository,
        private LeadModel $leadModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION => ['onIdentifyContact', 0],
        ];
    }

    public function onIdentifyContact(ContactIdentificationEvent $event): void
    {
        $clickthrough = $event->getClickthrough();
        $contact      = null;

        if (!empty($clickthrough['stat'])) {
            $stat = $this->statRepository->findOneBy(['trackingHash' => $clickthrough['stat']]);
            if (!$stat) {
                // Stat doesn't exist so use the tracked lead
                return;
            }
            if (
                isset($clickthrough['channel']['email'])
                && $stat->getEmail()
                && (int) $stat->getEmail()->getId() !== (int) $clickthrough['channel']['email']
            ) {
                // ID mismatch - fishy so use tracked lead
                return;
            }
            $contact = $stat->getLead();
        } elseif (!empty($clickthrough['lead'])) {
            $contact = $this->leadModel->getEntity($clickthrough['lead']);
        }

        if ($contact) {
            $event->setIdentifiedContact($contact, 'email');
        }
    }
}
