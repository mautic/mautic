<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\ChannelTrait;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ChannelTimelineInterface;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\VideoModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    use ChannelTrait;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        private PageModel $pageModel,
        private VideoModel $pageVideoModel,
        private TranslatorInterface $translator,
        RouterInterface $router,
        ModelFactory $modelFactory,
    ) {
        $this->router         = $router;

        $this->setModelFactory($modelFactory);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => [
                ['onTimelineGenerate', 0],
                ['onTimelineGenerateVideo', 0],
            ],
            LeadEvents::CURRENT_LEAD_CHANGED => ['onLeadChange', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        // Set available event types
        $eventTypeKey  = 'page.hit';
        $eventTypeName = $this->translator->trans('mautic.page.event.hit');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup(['pageList', 'hitDetails']);

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $hits = $this->pageModel->getHitRepository()->getLeadHits(
            $event->getLeadId(),
            $event->getQueryOptions()
        );

        // Add to counter
        $event->addToCounter($eventTypeKey, $hits);

        if (!$event->isEngagementCount()) {
            // Add the hits to the event array
            foreach ($hits['results'] as $hit) {
                $template = '@MauticPage/SubscribedEvents/Timeline/index.html.twig';
                $icon     = 'ri-link';

                if (!empty($hit['source'])) {
                    if ($channelModel = $this->getChannelModel($hit['source'])) {
                        if ($channelModel instanceof ChannelTimelineInterface) {
                            if ($overrideTemplate = $channelModel->getChannelTimelineTemplate($eventTypeKey, $hit)) {
                                $template = $overrideTemplate;
                            }

                            if ($overrideEventTypeName = $channelModel->getChannelTimelineLabel($eventTypeKey, $hit)) {
                                $eventTypeName = $overrideEventTypeName;
                            }

                            if ($overrideIcon = $channelModel->getChannelTimelineIcon($eventTypeKey, $hit)) {
                                $icon = $overrideIcon;
                            }
                        }

                        /* @deprecated - BC support to be removed in 3.0 */
                        // Allow a custom template if applicable
                        if (method_exists($channelModel, 'getPageHitLeadTimelineTemplate')) {
                            $template = $channelModel->getPageHitLeadTimelineTemplate($hit);
                        }
                        if (method_exists($channelModel, 'getPageHitLeadTimelineLabel')) {
                            $eventTypeName = $channelModel->getPageHitLeadTimelineLabel($hit);
                        }
                        if (method_exists($channelModel, 'getPageHitLeadTimelineIcon')) {
                            $icon = $channelModel->getPageHitLeadTimelineIcon($hit);
                        }
                        /* end deprecation */

                        if (!empty($hit['sourceId'])) {
                            if ($source = $this->getChannelEntityName($hit['source'], $hit['sourceId'], true)) {
                                $hit['sourceName']  = $source['name'];
                                $hit['sourceRoute'] = $source['url'];
                            }
                        }
                    }
                }

                if (!empty($hit['page_id'])) {
                    $page       = $this->pageModel->getEntity($hit['page_id']);
                    $eventLabel = [
                        'label' => $page->getTitle(),
                        'href'  => $this->router->generate('mautic_page_action', ['objectAction' => 'view', 'objectId' => $hit['page_id']]),
                    ];
                } else {
                    $eventLabel = [
                        'label'      => $hit['urlTitle'] ?? $hit['url'],
                        'href'       => $hit['url'],
                        'isExternal' => true,
                    ];
                }

                $contactId = $hit['lead_id'];
                unset($hit['lead_id']);

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $hit['hitId'],
                        'eventLabel' => $eventLabel,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $hit['dateHit'],
                        'extra'      => [
                            'hit' => $hit,
                        ],
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
    public function onTimelineGenerateVideo(LeadTimelineEvent $event): void
    {
        // Set available event types
        $eventTypeKey  = 'page.videohit';
        $eventTypeName = $this->translator->trans('mautic.page.event.videohit');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup(['pageList', 'hitDetails']);

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
                $template   = '@MauticPage/SubscribedEvents/Timeline/videohit.html.twig';
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
                        'icon'            => 'ri-vidicon-2-line',
                    ]
                );
            }
        }
    }

    public function onLeadChange(LeadChangeEvent $event): void
    {
        $this->pageModel->getHitRepository()->updateLeadByTrackingId(
            $event->getNewLead()->getId(),
            $event->getNewTrackingId(),
            $event->getOldTrackingId()
        );
    }

    public function onLeadMerge(LeadMergeEvent $event): void
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
