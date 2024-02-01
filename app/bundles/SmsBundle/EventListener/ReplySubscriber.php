<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\TimelineEventLogTrait;
use Mautic\LeadBundle\LeadEvents;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReplySubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    public function __construct(Translator $translator, LeadEventLogRepository $eventLogRepository)
    {
        $this->translator         = $translator;
        $this->eventLogRepository = $eventLogRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SmsEvents::ON_REPLY              => ['onReply', 0],
            LeadEvents::TIMELINE_ON_GENERATE => 'onTimelineGenerate',
        ];
    }

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
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addEvents(
            $event,
            'sms_reply',
            'mautic.sms.timeline.reply',
            'fa-mobile',
            'sms',
            'sms',
            'reply',
            '@MauticSms/SubscribedEvents/Timeline/reply.html.twig'
        );
    }
}
