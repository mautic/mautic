<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class EmailToUserSubscriber
{
    public function __construct(
        private SendEmailToUser $sendEmailToUser,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailEvents::ON_SENT_EMAIL_TO_USER)]
    public function onEmailToUser(TriggerExecutedEvent $event): void
    {
        $triggerEvent = $event->getTriggerEvent();
        $config       = $triggerEvent->getProperties();
        $lead         = $event->getLead();

        try {
            $this->sendEmailToUser->sendEmailToUsers($config, $lead);
            $event->setSucceded();
        } catch (EmailCouldNotBeSentException) {
            $event->setFailed();
        }
    }
}
