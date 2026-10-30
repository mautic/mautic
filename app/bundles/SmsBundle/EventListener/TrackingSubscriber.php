<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Entity\StatRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class TrackingSubscriber
{
    public function __construct(
        private StatRepository $statRepository,
    ) {
    }

    #[AsEventListener(
        event: ContactIdentificationEvent::class,
    )]
    public function onIdentifyContact(ContactIdentificationEvent $event): void
    {
        $clickthrough = $event->getClickthrough();

        // Nothing left to identify by so stick to the tracked lead
        if (empty($clickthrough['channel']['sms']) && empty($clickthrough['stat'])) {
            return;
        }

        /** @var Stat $stat */
        $stat = $this->statRepository->findOneBy(['trackingHash' => $clickthrough['stat']]);

        if (!$stat) {
            // Stat doesn't exist so use the tracked lead
            return;
        }

        if ($stat->getSms() && (int) $stat->getSms()->getId() !== (int) $clickthrough['channel']['sms']) {
            // ID mismatch - fishy so use tracked lead
            return;
        }

        if (!$contact = $stat->getLead()) {
            return;
        }

        $event->setIdentifiedContact($contact, 'sms');
    }
}
