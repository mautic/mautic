<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Attribution;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\Decorator\AttributionTrait;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\AttributionModel;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class LeadSubscriber
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::CURRENT_LEAD_CHANGED => ['onLeadChange', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'page.hit';
        $eventTypeName = $this->translator->trans('mautic.page.event.hit');
        $event->addEventType($eventTypeKey, $eventTypeName);

        if (!$event->isApplicable($eventTypeKey)) {

            return;
        }

        $lead = $event->getLead();

        /** @var \Mautic\PageBundle\Entity\HitRepository $hitRepository */
        $hitRepository = $this->em->getRepository('MauticPageBundle:Hit');;
        $hits          = $hitRepository->getLeadHits($lead->getId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $hits);

        if (!$event->isEngagementCount()) {

            $model = $this->factory->getModel('page.page');

            // Add the hits to the event array
            foreach ($hits['results'] as $hit) {
                $template = 'MauticPageBundle:SubscribedEvents\Timeline:index.html.php';

                if ($hit['source'] && $hit['sourceId']) {
                    $sourceModel = false;
                    try {
                        $sourceModel = $this->factory->getModel($hit['source']);
                    } catch (\Exception $exception) {
                        // Try a plugin

                        try {
                            $sourceModel = $this->factory->getModel('plugin.'.$hit['source']);
                        } catch (\Exception $exception) {
                            // No model found
                        }
                    }

                    if ($sourceModel) {
                        try {
                            $sourceEntity = $sourceModel->getEntity($hit['sourceId']);
                            if (method_exists($sourceEntity, $sourceModel->getNameGetter())) {
                                $hit['sourceName'] = $sourceEntity->{$sourceModel->getNameGetter()}();
                            }

                            $baseRouteName = str_replace('.', '_', $hit['source']);
                            if (method_exists($sourceModel, 'getActionRouteBase')) {
                                $baseRouteName = $sourceModel->getActionRouteBase();
                            }
                            $routeSourceName = 'mautic_'.$baseRouteName.'_action';

                            if ($this->factory->getRouter()->getRouteCollection()->get($routeSourceName) !== null) {
                                $hit['sourceRoute'] = $this->factory->getRouter()->generate(
                                    $routeSourceName,
                                    [
                                        'objectAction' => 'view',
                                        'objectId'     => $hit['sourceId']
                                    ]
                                );
                            }

                            // Allow a custom template if applicable
                            if (method_exists($sourceModel, 'getPageHitLeadTimelineTemplate')) {
                                $template = $sourceModel->getPageHitLeadTimelineTemplate($hit);
                            }

                            if (method_exists($sourceModel, 'getPageHitLeadTimelineLabel')) {
                                $eventTypeName = $sourceModel->getPageHitLeadTimelineLabel($hit);
                            }
                        } catch (\Exception $exception) {
                            // Not found
                        }
                    }
                }

                if (!empty($hit['page_id'])) {
                    $page       = $model->getEntity($hit['page_id']);
                    $eventLabel = [
                        'label' => $page->getTitle(),
                        'href'  => $this->router->generate('mautic_page_action', ['objectAction' => 'view', 'objectId' => $hit['page_id']])
                    ];
                } else {
                    $eventLabel = [
                        'label'      => (isset($hit['urlTitle'])) ? $hit['urlTitle'] : $hit['url'],
                        'href'       => $hit['url'],
                        'isExternal' => true
                    ];
                }
                $event->addEvent(
                    [
                        'event'           => $eventTypeKey,
                        'eventLabel'      => $eventLabel,
                        'eventType'       => $eventTypeName,
                        'timestamp'       => $hit['dateHit'],
                        'extra'           => [
                            'hit' => $hit
                        ],
                        'contentTemplate' => $template,
                        'icon'            => 'fa-link'
                    ]
                );
            }
        }
    }

    /**
     * @param LeadChangeEvent $event
     */
    public function onLeadChange(LeadChangeEvent $event)
    {
        $this->factory->getModel('page')->getHitRepository()->updateLeadByTrackingId(
            $event->getNewLead()->getId(),
            $event->getNewTrackingId(),
            $event->getOldTrackingId()
        );
    }

    /**
     * @param LeadMergeEvent $event
     */
    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->factory->getModel('page')->getHitRepository()->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }
}
