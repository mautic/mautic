<?php

namespace Mautic\EmailBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;

final readonly class TrackingSubscriber
{
    public function __construct(
        private StatRepository $statRepository,
    ) {
    }

    #[AsEventListener(event: ContactIdentificationEvent::class)]
    public function onIdentifyContact(ContactIdentificationEvent $event): void
    {
        $clickthrough = $event->getClickthrough();

        // Nothing left to identify by so stick to the tracked lead
        if (empty($clickthrough['stat'])) {
            return;
        }

        /** @var Stat $stat */
        $stat = $this->statRepository->findOneBy(['trackingHash' => $clickthrough['stat']]);

        if (!$stat) {
            // Stat doesn't exist so use the tracked lead
            return;
        }

        if (isset($clickthrough['channel']['email']) && $stat->getEmail() && (int) $stat->getEmail()->getId() !== (int) $clickthrough['channel']['email']) {
            // ID mismatch - fishy so use tracked lead
            return;
        }

        if (!$contact = $stat->getLead()) {
            return;
        }

        $event->setIdentifiedContact($contact, 'email');
    }
}
