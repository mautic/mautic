<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;

/**
 * Class LeadSubscriber
 *
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0),
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => array('onListChoicesGenerate', 0),
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => array('onListOperatorsGenerate', 0),
            LeadEvents::LIST_FILTERS_ON_FILTERING => array('onListFiltering', 0),
        );
    }

    public function onListOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        // TODO: add custom operators
    }

    /**
     * @param LeadTimelineEvent $event
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $activeProducts = [];

        if (CitrixHelper::isAuthorized('Gotowebinar')) {
            $activeProducts[] = CitrixProducts::GOTOWEBINAR;
        }

        if (CitrixHelper::isAuthorized('Gotomeeting')) {
            $activeProducts[] = CitrixProducts::GOTOMEETING;
        }

        if (CitrixHelper::isAuthorized('Gototraining')) {
            $activeProducts[] = CitrixProducts::GOTOTRAINING;
        }

        if (CitrixHelper::isAuthorized('Gotoassist')) {
            $activeProducts[] = CitrixProducts::GOTOASSIST;
        }

        if (0 === count($activeProducts)) {
            return;
        }

        $leadEmail = $event->getLead()->getEmail();
        if ('' === $leadEmail) {
            return;
        }

        /** @var CitrixModel $citrixModel */
        $citrixModel = CitrixHelper::getContainer()->get('mautic.model.factory')->getModel('citrix.citrix');

        foreach ($activeProducts as $product) {
            $eventTypeRegistered = $product.'.registered';
            $eventTypeRegisteredName = $this->translator->trans('plugin.citrix.timeline.'.$product.'.registered');
            $event->addEventType($eventTypeRegistered, $eventTypeRegisteredName);

            $eventTypeAttended = $product.'.attended';
            $eventTypeAttendedName = $this->translator->trans('plugin.citrix.timeline.'.$product.'.attended');
            $event->addEventType($eventTypeAttended, $eventTypeAttendedName);

            $isApplicable = [
                CitrixEventTypes::REGISTERED => $event->isApplicable($eventTypeRegistered),
                CitrixEventTypes::ATTENDED => $event->isApplicable($eventTypeAttended),
            ];

            $citrixEvents = $citrixModel->getEventsByLeadEmail($product, $leadEmail);
            if (0 !== count($citrixEvents)) {
                /** @var CitrixEvent $citrixEvent */
                foreach ($citrixEvents as $citrixEvent) {
                    $eventType = $citrixEvent->getEventType();
                    if ($eventType === CitrixEventTypes::REGISTERED) {
                        $timelineEventType = $eventTypeRegistered;
                        $timelineEventLabel = $eventTypeRegisteredName;
                    } else {
                        if ($eventType === CitrixEventTypes::ATTENDED) {
                            $timelineEventType = $eventTypeAttended;
                            $timelineEventLabel = $eventTypeAttendedName;
                        } else {
                            continue;
                        }
                    }

                    if (!$isApplicable[$eventType]) {
                        continue;
                    }

                    $event->addEvent(
                        array(
                            'event' => $timelineEventType,
                            'eventLabel' => $timelineEventLabel,
                            'timestamp' => $citrixEvent->getEventDate(),
                            'extra' => [
                                'eventName' => $citrixEvent->getEventName(),
                            ],
                            'contentTemplate' => 'MauticCitrixBundle:SubscribedEvents\Timeline:citrix_event.html.php',
                        )
                    );
                }
            }
        } // foreach $product
    }

    /**
     *
     * @param LeadListFiltersChoicesEvent $event
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function onListChoicesGenerate(LeadListFiltersChoicesEvent $event)
    {
        $activeProducts = [];

        if (CitrixHelper::isAuthorized('Gotowebinar')) {
            $activeProducts[] = CitrixProducts::GOTOWEBINAR;
        }

        if (CitrixHelper::isAuthorized('Gotomeeting')) {
            $activeProducts[] = CitrixProducts::GOTOMEETING;
        }

        if (CitrixHelper::isAuthorized('Gototraining')) {
            $activeProducts[] = CitrixProducts::GOTOTRAINING;
        }

        if (CitrixHelper::isAuthorized('Gotoassist')) {
            $activeProducts[] = CitrixProducts::GOTOASSIST;
        }

        if (0 === count($activeProducts)) {
            return;
        }

        /** @var CitrixModel $citrixModel */
        $citrixModel = CitrixHelper::getContainer()->get('mautic.model.factory')->getModel('citrix.citrix');

        foreach ($activeProducts as $product) {
            $eventNames = $citrixModel->getDistinctEventNames($product);
            $eventNames = array_combine($eventNames, $eventNames);

            $eventNamesWithoutAny = array_merge(
                array(
                    '-' => '-',
                ),
                $eventNames
            );

            $eventNamesWithAny = array_merge(
                array(
                    '-' => '-',
                    'any' => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.any'),
                ),
                $eventNames
            );

            $event->addChoice(
                $product.'-registration',
                array(
                    'label' => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.registration'),
                    'properties' => array(
                        'type' => 'select',
                        'list' => $eventNamesWithAny,
                    ),
                    'operators' => array(
                        'include' => array('in', '!in'),
                    ),
                )
            );

            $event->addChoice(
                $product.'-attendance',
                array(
                    'label' => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.attendance'),
                    'properties' => array(
                        'type' => 'select',
                        'list' => $eventNamesWithAny,
                    ),
                    'operators' => array(
                        'include' => array('in', '!in'),
                    ),
                )
            );

            $event->addChoice(
                $product.'-no-attendance',
                array(
                    'label' => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.no.attendance'),
                    'properties' => array(
                        'type' => 'select',
                        'list' => $eventNamesWithoutAny,
                    ),
                    'operators' => array(
                        'include' => array('in'),
                    ),
                )
            );
        } // foreach $product
    }

    /**
     *
     * @param LeadListFilteringEvent $event
     */
    public function onListFiltering(LeadListFilteringEvent $event)
    {
        $activeProducts = [];

        if (CitrixHelper::isAuthorized('Gotowebinar')) {
            $activeProducts[] = CitrixProducts::GOTOWEBINAR;
        }

        if (CitrixHelper::isAuthorized('Gotomeeting')) {
            $activeProducts[] = CitrixProducts::GOTOMEETING;
        }

        if (CitrixHelper::isAuthorized('Gototraining')) {
            $activeProducts[] = CitrixProducts::GOTOTRAINING;
        }

        if (CitrixHelper::isAuthorized('Gotoassist')) {
            $activeProducts[] = CitrixProducts::GOTOASSIST;
        }

        if (0 === count($activeProducts)) {
            return;
        }

        $details = $event->getDetails();
        $leadId = $event->getLeadId();
        $em = $event->getEntityManager();
        $q = $event->getQueryBuilder();
        $alias = $event->getAlias();
        $func = $event->getFunc();
        $currentFilter = $details['field'];
        $citrixEventsTable = $em->getClassMetadata('MauticCitrixBundle:CitrixEvent')->getTableName();

        foreach ($activeProducts as $product) {
            $eventFilters = [$product.'-registration', $product.'-attendance', $product.'-no-attendance'];

            if (in_array($currentFilter, $eventFilters, true)) {
                $eventNames = $details['filter'];
                $eventNamesForQuery = array_map(
                    function ($name) {
                        return "'".$name."'";
                    },
                    $eventNames
                );

                $isAnyEvent = in_array('any', $eventNames, true);

                $leadEmail = '';
                if ('' !== $leadId) {
                    /** @var LeadRepository $leadRepository */
                    $leadRepository = $em->getRepository('MauticLeadBundle:Lead');
                    /** @var Lead $lead */
                    $lead = $leadRepository->getEntity($leadId);
                    $leadEmail = $lead->getEmail();
                }

                $subQueriesSQL = [];

                $eventTypes = [CitrixEventTypes::REGISTERED, CitrixEventTypes::ATTENDED];
                foreach ($eventTypes as $k => $eventType) {
                    $query = $em->getConnection()->createQueryBuilder()
                        ->select('null')
                        ->from($citrixEventsTable, $alias.$k);

                    if (!$isAnyEvent) {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq($alias.$k.'.product', "'".$product."'"),
                                $q->expr()->eq($alias.$k.'.email', 'l.email'),
                                $q->expr()->eq($alias.$k.'.event_type', "'".$eventType."'"),
                                $q->expr()->in($alias.$k.'.event_name', $eventNamesForQuery)
                            )
                        );
                    } else {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq($alias.$k.'.product', "'".$product."'"),
                                $q->expr()->eq($alias.$k.'.email', 'l.email'),
                                $q->expr()->eq($alias.$k.'.event_type', "'".$eventType."'")
                            )
                        );
                    }

                    if ('' !== $leadEmail) {
                        $query->andWhere(
                            $query->expr()->eq($alias.$k.'.email', $leadEmail)
                        );
                    }

                    $subQueriesSQL[$eventType] = $query->getSQL();
                } // foreach $eventType

                $subQuery = '';

                if ($func === 'in') {
                    if ($currentFilter === $product.'-registration') {
                        $subQuery = 'EXISTS ('.$subQueriesSQL[CitrixEventTypes::REGISTERED].')';
                    } else {
                        if ($currentFilter === $product.'-attendance') {
                            $subQuery = 'EXISTS ('.$subQueriesSQL[CitrixEventTypes::ATTENDED].')';
                        } else {
                            if ($currentFilter === $product.'-no-attendance') {
                                $queryNbRegistered = $em->getConnection()->createQueryBuilder()
                                    ->select('count(*)')
                                    ->from($citrixEventsTable, $alias.'sub1')
                                    ->where(
                                        $q->expr()->andX(
                                            $q->expr()->eq(
                                                $alias.'sub1.event_type',
                                                "'".CitrixEventTypes::REGISTERED."'"
                                            ),
                                            $q->expr()->in($alias.'sub1.event_name', $eventNamesForQuery),
                                            $q->expr()->eq($alias.'sub1.email', $alias.'.email')
                                        )
                                    )->getSQL();

                                $queryNbParticipated = $em->getConnection()->createQueryBuilder()
                                    ->select('count(*)')
                                    ->from($citrixEventsTable, $alias.'sub2')
                                    ->where(
                                        $q->expr()->andX(
                                            $q->expr()->eq(
                                                $alias.'sub2.event_type',
                                                "'".CitrixEventTypes::ATTENDED."'"
                                            ),
                                            $q->expr()->in($alias.'sub2.event_name', $eventNamesForQuery),
                                            $q->expr()->eq($alias.'sub2.email', $alias.'.email')
                                        )
                                    )->getSQL();

                                $subQuery = '(('.$queryNbRegistered.') > ('.$queryNbParticipated.')) AND '.$alias.'.email = l.email';

                                if ('' !== $leadEmail) {
                                    $subQuery .= ' AND '.$alias.".email='".$leadEmail."'";
                                }

                                $subQuery = 'EXISTS ( SELECT null FROM '.$citrixEventsTable.' AS '.$alias.' WHERE ( '.$subQuery.'))';
                            }
                        }
                    }
                } else {
                    if ($func === 'notIn') {

                        if ($currentFilter === $product.'-registration') {
                            $subQuery = 'NOT EXISTS ('.$subQueriesSQL[CitrixEventTypes::REGISTERED].')';
                        } else {
                            if ($currentFilter === $product.'-attendance') {
                                $subQuery = 'NOT EXISTS ('.$subQueriesSQL[CitrixEventTypes::ATTENDED].')';
                            }
                        }
                    }
                }

                $event->setSubQuery($subQuery);
                $event->setFilteringStatus(true);
            }
        } // foreach $product
    }

}
