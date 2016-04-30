<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CalendarBundle\Event\EventGeneratorEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CalendarSubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class CalendarSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CalendarEvents::CALENDAR_ON_GENERATE => array('onCalendarGenerate', 0),
            CalendarEvents::CALENDAR_EVENT_ON_GENERATE => array('onCalendarEventGenerate', 0)
        );
    }

    /**
     * Adds events to the calendar
     *
     * @param CalendarGeneratorEvent $event
     *
     * @return void
     * @todo   This method is only a model and should be removed when actual data is being populated
     */
    public function onCalendarGenerate(CalendarGeneratorEvent $event)
    {
        $dates  = $event->getDates();
        $router = $this->factory->getRouter();

        $commonSelect = 'p.title, p.id as page_id, c.color';
        $eventTypes = array(
            'publish.up'   => array('dateName' => 'publish_up', 'setter' => 'PublishUp'),
            'publish.down' => array('dateName' => 'publish_down', 'setter' => 'PublishDown')
        );

        $query = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX . 'pages', 'p')
            ->leftJoin('p', MAUTIC_TABLE_PREFIX . 'categories', 'c', 'c.id = p.category_id AND c.bundle=:bundle')
            ->setParameter('bundle', 'page')
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(50);

        foreach ($eventTypes as $eventKey => $eventType) {
            $query->select($commonSelect . ', ' . $eventType['dateName'] . ' AS start')
                ->where($query->expr()->andX(
                    $query->expr()->gte('p.' . $eventType['dateName'], ':start'),
                    $query->expr()->lte('p.' . $eventType['dateName'], ':end')
                ));

            $results = $query->execute()->fetchAll();

            // We need to convert the date to a ISO8601 compliant string
            foreach ($results as &$object) {
                $date = new DateTimeHelper($object['start']);
                $eventTitle = $this->translator->trans('mautic.page.event.' . $eventKey, array('%page%' => $object['title']));
                $object['start'] = $date->toLocalString(\DateTime::ISO8601);
                $object['setter'] = $eventType['setter'];
                $object['entityId'] = $object['page_id'];
                $object['entityType'] = 'page';
                $object['editable'] = true;
                $object['url']   = $router->generate('mautic_calendar_action', array(
                    'objectAction' => 'edit',
                    'source' => 'page',
                    'objectId' => $object['page_id'],
                    'startDate' => $date->toLocalString()
                ), true);
                $object['viewUrl']   = $router->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $object['page_id']), true);
                $object['attr']  = array(
                    'data-toggle' => 'ajaxmodal',
                    'data-target' => '#CalendarEditModal',
                    'data-header' => $eventTitle
                );
                $object['description'] = $this->translator->trans('mautic.page.event.' . $eventKey . '.description', array('%page%' => $object['title']));
                $object['title'] = $eventTitle;
            }

            $event->addEvents($results);
        }
    }

    /**
     * Let the calendar to edit / create new entities
     *
     * @param EventGeneratorEvent $event
     *
     * @return void
     */
    public function onCalendarEventGenerate(EventGeneratorEvent $event)
    {
        $source     = $event->getSource();

        if ($source != 'page') {
            return;
        }

        $entityId   = $event->getEntityId();

        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model   = $this->factory->getModel('page.page');
        $entity  = $model->getEntity($entityId);

        $event->setModel($model);
        $event->setEntity($entity);
        $event->setContentTemplate('MauticPageBundle:SubscribedEvents\Calendar:modal.html.php');
        $event->setFormName('page_publish_dates');
        $event->setAccess($this->factory->getSecurity()->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()));
    }
}
