<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Proceses queue (Beanstalk, RabitMQ, ...) jobs.
 */
class QueueSubscriber implements EventSubscriberInterface
{
    public function __construct(private EmailModel $emailModel)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            QueueEvents::EMAIL_HIT => ['onEmailHit', 0],
        ];
    }

    public function onEmailHit(QueueConsumerEvent $event)
    {
        $payload = $event->getPayload();
        $this->emailModel->hitEmail($payload['idHash'], $payload['request'], false, false);
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }
}
