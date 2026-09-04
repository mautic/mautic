<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TimelineEventLogSubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    public function __construct(
        Translator $translator,
        LeadEventLogRepository $leadEventLogRepository,
    ) {
        $this->translator         = $translator;
        $this->eventLogRepository = $leadEventLogRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadTimelineEvent::class => ['onTimelineGenerate', 0],
        ];
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addEvents(
            $event,
            'lead.source.created',
            'mautic.lead.timeline.created_source',
            'ri-spy-line',
            null,
            null,
            'created_contact'
        );

        $this->addEvents(
            $event,
            'lead.source.identified',
            'mautic.lead.timeline.identified_source',
            'ri-user-6-fill',
            null,
            null,
            'identified_contact'
        );
    }
}
