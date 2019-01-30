<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TimelineEventLogSubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    /**
     * TimelineEventLogSubscriber constructor.
     *
     * @param TranslatorInterface    $translator
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        LeadEventLogRepository $leadEventLogRepository
    ) {
        $this->translator         = $translator;
        $this->eventLogRepository = $leadEventLogRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addEvents(
            $event,
            'lead.source.created',
            'mautic.lead.timeline.created_source',
            'fa-user-secret',
            null,
            null,
            'created_contact'
        );

        $this->addEvents(
            $event,
            'lead.source.identified',
            'mautic.lead.timeline.identified_source',
            'fa-user',
            null,
            null,
            'identified_contact'
        );
    }
}
