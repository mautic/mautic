<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CalendarBundle\Event\EventGeneratorEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\PageBundle\Model\PageModel;

/**
 * Class CalendarSubscriber.
 */
class CalendarSubscriber extends CommonSubscriber
{
    /**
     * @var PageModel
     */
    protected $pageModel;

    /**
     * CalendarSubscriber constructor.
     *
     * @param PageModel $pageModel
     */
    public function __construct(PageModel $pageModel)
    {
        $this->pageModel = $pageModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CalendarEvents::CALENDAR_ON_GENERATE       => ['onCalendarGenerate', 0],
            CalendarEvents::CALENDAR_EVENT_ON_GENERATE => ['onCalendarEventGenerate', 0],
        ];
    }

    /**
     * Adds events to the calendar.
     *
     * @param CalendarGeneratorEvent $event
     *
     * @todo   This method is only a model and should be removed when actual data is being populated
     */
    public function onCalendarGenerate(CalendarGeneratorEvent $event)
    {
        $dates = $event->getDates();

        $commonSelect = 'p.title, p.id as page_id, c.color';
        $eventTypes   = [
            'publish.up'   => ['dateName' => 'publish_up', 'setter' => 'PublishUp'],
            'publish.down' => ['dateName' => 'publish_down', 'setter' => 'PublishDown'],
        ];

        $query = $this->em->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'pages', 'p')
            ->leftJoin('p', MAUTIC_TABLE_PREFIX.'categories', 'c', 'c.id = p.category_id AND c.bundle=:bundle')
            ->setParameter('bundle', 'page')
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(50);

        foreach ($eventTypes as $eventKey => $eventType) {
            $query->select($commonSelect.', '.$eventType['dateName'].' AS start')
                ->where($query->expr()->andX(
                    $query->expr()->gte('p.'.$eventType['dateName'], ':start'),
                    $query->expr()->lte('p.'.$eventType['dateName'], ':end')
                ));

            $results = $query->execute()->fetchAll();

            // We need to convert the date to a ISO8601 compliant string
            foreach ($results as &$object) {
                $date                 = new DateTimeHelper($object['start']);
                $eventTitle           = $this->translator->trans('mautic.page.event.'.$eventKey, ['%page%' => $object['title']]);
                $object['start']      = $date->toLocalString(\DateTime::ISO8601);
                $object['setter']     = $eventType['setter'];
                $object['entityId']   = $object['page_id'];
                $object['entityType'] = 'page';
                $object['editable']   = true;
                $object['url']        = $this->router->generate('mautic_calendar_action', [
                    'objectAction' => 'edit',
                    'source'       => 'page',
                    'objectId'     => $object['page_id'],
                    'startDate'    => $date->toLocalString(),
                ], true);
                $object['viewUrl'] = $this->router->generate('mautic_page_action', ['objectAction' => 'view', 'objectId' => $object['page_id']], true);
                $object['attr']    = [
                    'data-toggle' => 'ajaxmodal',
                    'data-target' => '#CalendarEditModal',
                    'data-header' => $eventTitle,
                ];
                $object['description'] = $this->translator->trans('mautic.page.event.'.$eventKey.'.description', ['%page%' => $object['title']]);
                $object['title']       = $eventTitle;
            }

            $event->addEvents($results);
        }
    }

    /**
     * Let the calendar to edit / create new entities.
     *
     * @param EventGeneratorEvent $event
     */
    public function onCalendarEventGenerate(EventGeneratorEvent $event)
    {
        $source = $event->getSource();

        if ($source != 'page') {
            return;
        }

        $entityId = $event->getEntityId();
        $entity   = $this->pageModel->getEntity($entityId);

        $event->setModel($this->pageModel);
        $event->setEntity($entity);
        $event->setContentTemplate('MauticPageBundle:SubscribedEvents\Calendar:modal.html.php');
        $event->setFormName('page_publish_dates');
        $event->setAccess($this->security->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()));
    }
}
