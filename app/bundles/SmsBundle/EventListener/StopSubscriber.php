<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\SmsBundle\Event\ReplyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class StopSubscriber
{
    public function __construct(
        private DoNotContactModel $doNotContactModel,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ReplyEvent::class)]
    public function onReply(ReplyEvent $event): void
    {
        $message = $event->getMessage();

        if ('stop' === strtolower($message)) {
            // Unsubscribe the contact
            $this->doNotContactModel->addDncForContact($event->getContact()->getId(), 'sms', DoNotContact::UNSUBSCRIBED);
        }
    }
}
