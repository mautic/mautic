<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\SmsBundle\Event\ReplyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class StopSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DoNotContactModel $doNotContactModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReplyEvent::class => ['onReply', 0],
        ];
    }

    public function onReply(ReplyEvent $event): void
    {
        $message = $event->getMessage();

        if ('stop' === strtolower($message)) {
            // Unsubscribe the contact
            $this->doNotContactModel->addDncForContact($event->getContact()->getId(), 'sms', DoNotContact::UNSUBSCRIBED);
        }
    }
}
