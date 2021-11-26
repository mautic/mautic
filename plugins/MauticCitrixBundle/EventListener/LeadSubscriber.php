<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var CitrixModel
     */
    private $model;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CitrixModel $model, TranslatorInterface $translator)
    {
        $this->model      = $model;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE             => ['onTimelineGenerate', 0],
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onListChoicesGenerate', 0],
            LeadEvents::LIST_FILTERS_ON_FILTERING        => ['onListFiltering', 0],
        ];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $activeProducts = [];
        foreach (CitrixProducts::toArray() as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        foreach ($activeProducts as $product) {
            foreach ([CitrixEventTypes::REGISTERED, CitrixEventTypes::ATTENDED] as $type) {
                $eventType = $product.'.'.$type;
                if (!$event->isApplicable($eventType)) {
                    continue;
                }

                $eventTypeLabel = $this->translator->trans('plugin.citrix.timeline.event.'.$product.'.'.$type);
                $eventTypeName  = $this->translator->trans('plugin.citrix.timeline.'.$product.'.'.$type);
                $event->addEventType($eventType, $eventTypeName);

                $citrixEvents = $this->model->getRepository()->getEventsForTimeline(
                    [$product, $type],
                    $event->getLeadId(),
                    $event->getQueryOptions()
                );

                // Add total number to counter
                $event->addToCounter($eventType, $citrixEvents);

                if (!$event->isEngagementCount()) {
                    if ($citrixEvents['total']) {
                        // Use a single entity class to help parse the name, description, etc without hydrating entities for every single event
                        $entity = new CitrixEvent();

                        foreach ($citrixEvents['results'] as $citrixEvent) {
                            $entity->setProduct($citrixEvent['product'])
                                ->setEventName($citrixEvent['event_name'])
                                ->setEventDesc($citrixEvent['event_desc'])
                                ->setEventType($citrixEvent['event_type'])
                                ->setEventDate($citrixEvent['event_date']);

                            $event->addEvent(
                                [
                                    'event'      => $eventType,
                                    'eventId'    => $eventType.$citrixEvent['id'],
                                    'eventLabel' => $eventTypeName.' - '.$entity->getEventDesc(),
                                    'eventType'  => $eventTypeLabel,
                                    'timestamp'  => $entity->getEventDate(),
                                    'extra'      => [
                                        'eventName' => $entity->getEventNameOnly(),
                                        'eventId'   => $entity->getEventId(),
                                        'eventDesc' => $entity->getEventDesc(),
                                        'joinUrl'   => $entity->getJoinUrl(),
                                    ],
                                    'contentTemplate' => 'MauticCitrixBundle:SubscribedEvents\Timeline:citrix_event.html.php',
                                    'contactId'       => $citrixEvent['lead_id'],
                                ]
                            );
                        }
                    }
                }
            }
        } // foreach $product
    }

    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function onListChoicesGenerate(LeadListFiltersChoicesEvent $event)
    {
        if (false === strpos($event->getRoute(), 'mautic_segment_action')) {
            return;
        }

        $activeProducts = [];
        foreach (CitrixProducts::toArray() as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        foreach ($activeProducts as $product) {
            $eventNames = $this->model->getDistinctEventNamesDesc($product);

            $eventNamesWithoutAny = array_merge(
                [
                    '-' => '-',
                ],
                $eventNames
            );

            $eventNamesWithAny = array_merge(
                [
                    '-'   => '-',
                    'any' => $this->translator->trans('plugin.citrix.event.'.$product.'.any'),
                ],
                $eventNames
            );

            if (in_array($product, [CitrixProducts::GOTOWEBINAR, CitrixProducts::GOTOTRAINING])) {
                $event->addChoice(
                    'lead',
                    $product.'-registration',
                    [
                        'label'      => $this->translator->trans('plugin.citrix.event.'.$product.'.registration'),
                        'properties' => [
                            'type' => 'select',
                            'list' => $eventNamesWithAny,
                        ],
                        'operators' => [
                            $this->translator->trans('mautic.core.operator.in')    => 'in',
                            $this->translator->trans('mautic.core.operator.notin') => '!in',
                        ],
                    ]
                );
            }

            $event->addChoice(
                'lead',
                $product.'-attendance',
                [
                    'label'      => $this->translator->trans('plugin.citrix.event.'.$product.'.attendance'),
                    'properties' => [
                        'type' => 'select',
                        'list' => $eventNamesWithAny,
                    ],
                    'operators' => [
                        $this->translator->trans('mautic.core.operator.in')    => 'in',
                        $this->translator->trans('mautic.core.operator.notin') => '!in',
                    ],
                ]
            );

            $event->addChoice(
                'lead',
                $product.'-no-attendance',
                [
                    'label'      => $this->translator->trans('plugin.citrix.event.'.$product.'.no.attendance'),
                    'properties' => [
                        'type' => 'select',
                        'list' => $eventNamesWithoutAny,
                    ],
                    'operators' => [
                        $this->translator->trans('mautic.core.operator.in') => 'in',
                    ],
                ]
            );
        } // foreach $product
    }

    public function onListFiltering(LeadListFilteringEvent $event)
    {
        $activeProducts = [];
        foreach (CitrixProducts::toArray() as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        $details           = $event->getDetails();
        $leadId            = $event->getLeadId();
        $em                = $event->getEntityManager();
        $q                 = $event->getQueryBuilder();
        $alias             = $event->getAlias();
        $func              = $event->getFunc();
        $currentFilter     = $details['field'];
        $citrixEventsTable = $em->getClassMetadata('MauticCitrixBundle:CitrixEvent')->getTableName();

        foreach ($activeProducts as $product) {
            $eventFilters = [$product.'-registration', $product.'-attendance', $product.'-no-attendance'];

            if (in_array($currentFilter, $eventFilters, true)) {
                $eventNames = $details['filter'];
                if (!is_iterable($eventNames)) {
                    $eventNames = [$eventNames];
                }
                $isAnyEvent = in_array('any', $eventNames, true);
                $eventNames = array_map(function ($v) use ($q) {
                    return $q->expr()->literal($v);
                }, $eventNames);
                $subQueriesSQL = [];

                $eventTypes = [CitrixEventTypes::REGISTERED, CitrixEventTypes::ATTENDED];
                foreach ($eventTypes as $k => $eventType) {
                    $query = $em->getConnection()->createQueryBuilder()
                                ->select('null')
                                ->from($citrixEventsTable, $alias.$k);

                    if (!$isAnyEvent) {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq($alias.$k.'.product', $q->expr()->literal($product)),
                                $q->expr()->eq($alias.$k.'.event_type', $q->expr()->literal($eventType)),
                                $q->expr()->in($alias.$k.'.event_name', $eventNames),
                                $q->expr()->eq($alias.$k.'.lead_id', 'l.id')
                            )
                        );
                    } else {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq($alias.$k.'.product', $q->expr()->literal($product)),
                                $q->expr()->eq($alias.$k.'.event_type', $q->expr()->literal($eventType)),
                                $q->expr()->eq($alias.$k.'.lead_id', 'l.id')
                            )
                        );
                    }

                    if ($leadId) {
                        $query->andWhere(
                            $query->expr()->eq($alias.$k.'.lead_id', $leadId)
                        );
                    }

                    $subQueriesSQL[$eventType] = $query->getSQL();
                } // foreach $eventType

                switch ($currentFilter) {
                    case $product.'-registration':
                        $event->setSubQuery(
                            sprintf('%s (%s)', 'in' == $func ? 'EXISTS' : 'NOT EXISTS', $subQueriesSQL[CitrixEventTypes::REGISTERED])
                        );
                        break;

                    case $product.'-attendance':
                        $event->setSubQuery(
                            sprintf('%s (%s)', 'in' == $func ? 'EXISTS' : 'NOT EXISTS', $subQueriesSQL[CitrixEventTypes::ATTENDED])
                        );
                        break;

                    case $product.'-no-attendance':
                        $queries = [sprintf('%s (%s)', 'in' == $func ? 'NOT EXISTS' : 'EXISTS', $subQueriesSQL[CitrixEventTypes::ATTENDED])];

                        if (in_array($product, [CitrixProducts::GOTOWEBINAR, CitrixProducts::GOTOTRAINING])) {
                            // These products track registration
                            $queries[] = sprintf('EXISTS (%s)', $subQueriesSQL[CitrixEventTypes::REGISTERED]);
                        }

                        $event->setSubQuery(implode(' AND ', $queries));

                        break;
                }
            }
        } // foreach $product
    }
}
