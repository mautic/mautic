<?php

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CoreBundle\EventListener\ChannelTrait;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\VideoModel;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    use ChannelTrait;

    /**
     * @var PageModel
     */
    private $pageModel;

    /**
     * @var VideoModel
     */
    private $pageVideoModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    private FocusModel $focusModel;

    public function __construct(
        PageModel $pageModel,
        VideoModel $pageVideoModel,
        TranslatorInterface $translator,
        RouterInterface $router,
        FocusModel $focusModel
    ) {
        $this->pageModel      = $pageModel;
        $this->pageVideoModel = $pageVideoModel;
        $this->translator     = $translator;
        $this->router         = $router;
        $this->focusModel     = $focusModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE      => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $eventTypeKey  = 'focus.view';
        $eventTypeName = $this->translator->trans('mautic.focus.event.view');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('focusList');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $contactId        = $event->getLead()->getId();
        $statsViewsByLead = $this->focusModel->getStatRepository()->getStatsViewByLead($contactId, $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $statsViewsByLead);

        if (!$event->isEngagementCount()) {
            // Add the view to the event array
            foreach ($statsViewsByLead['result'] as $statsView) {
                $template = 'MauticFocusBundle:SubscribedEvents\Timeline:index.html.php';
                $icon     = 'fa-search';

                $eventLabel = [
                    'label' => $statsView['focus']['name'],
                    'href'  => $this->router->generate('mautic_focus_action', ['objectAction' => 'view', 'objectId' => $statsView['focus']['id']]),
                ];

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $statsView['id'],
                        'eventLabel' => $eventLabel,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $statsView['dateAdded'],
//                        'extra' => [
//                            'view' => $statsView,
//                        ],
                        'contentTemplate' => $template,
                        'icon'            => $icon,
                        'contactId'       => $contactId,
                    ]
                );
            }
        }
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerateVideo(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'page.videohit';
        $eventTypeName = $this->translator->trans('mautic.page.event.videohit');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('pageList', 'hitDetails');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $hits = $this->pageVideoModel->getHitRepository()->getTimelineStats(
            $event->getLeadId(),
            $event->getQueryOptions()
        );

        $event->addToCounter($eventTypeKey, $hits);

        if (!$event->isEngagementCount()) {
            // Add the hits to the event array
            foreach ($hits['results'] as $hit) {
                $template   = 'MauticPageBundle:SubscribedEvents\Timeline:videohit.html.php';
                $eventLabel = $eventTypeName;

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventLabel' => $eventLabel,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $hit['date_hit'],
                        'extra'      => [
                            'hit' => $hit,
                        ],
                        'contentTemplate' => $template,
                        'icon'            => 'fa-video-camera',
                    ]
                );
            }
        }
    }

    public function onLeadChange(LeadChangeEvent $event)
    {
        $this->pageModel->getHitRepository()->updateLeadByTrackingId(
            $event->getNewLead()->getId(),
            $event->getNewTrackingId(),
            $event->getOldTrackingId()
        );
    }

    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->pageModel->getHitRepository()->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->pageVideoModel->getHitRepository()->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
