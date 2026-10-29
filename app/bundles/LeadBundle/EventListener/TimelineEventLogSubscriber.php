<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;

final class TimelineEventLogSubscriber
{
    use TimelineEventLogTrait;

    public function __construct(
        Translator $translator,
        LeadEventLogRepository $leadEventLogRepository,
    ) {
        $this->translator         = $translator;
        $this->eventLogRepository = $leadEventLogRepository;
    }

    #[AsEventListener(event: LeadTimelineEvent::class)]
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
