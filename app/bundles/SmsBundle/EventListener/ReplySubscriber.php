<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\TimelineEventLogTrait;
use Mautic\SmsBundle\Event\ReplyEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ReplySubscriber
{
    use TimelineEventLogTrait;

    public function __construct(Translator $translator, LeadEventLogRepository $eventLogRepository)
    {
        $this->translator         = $translator;
        $this->eventLogRepository = $eventLogRepository;
    }

    #[AsEventListener(
        event: ReplyEvent::class,
        priority: 0
    )]
    public function onReply(ReplyEvent $event): void
    {
        $message = $event->getMessage();
        $contact = $event->getContact();

        $log = new LeadEventLog();
        $log
            ->setLead($contact)
            ->setBundle('sms')
            ->setObject('sms')
            ->setAction('reply')
            ->setProperties(
                [
                    'message' => InputHelper::clean($message),
                ]
            );

        $this->eventLogRepository->saveEntity($log);
        $this->eventLogRepository->detachEntity($log);
        $event->setEventLog($log);
    }

    #[AsEventListener(event: LeadTimelineEvent::class)]
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addEvents(
            $event,
            'sms_reply',
            'mautic.sms.timeline.reply',
            'ri-smartphone-line',
            'sms',
            'sms',
            'reply',
            '@MauticSms/SubscribedEvents/Timeline/reply.html.twig'
        );
    }
}
