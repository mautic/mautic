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
            CalendarEvents::CALENDAR_ON_GENERATE => array('onCalendarGenerate', 0)
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

        $commonSelect = 'p.title, p.id as page_id';
        $eventTypes = array(
            'publish.up'   => array('dateName' => 'p.publish_up'),
            'publish.down' => array('dateName' => 'p.publish_down')
        );

        $query = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX . 'pages', 'p')
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(50);

        foreach ($eventTypes as $eventKey => $eventType) {
            $query->select($commonSelect . ', ' . $eventType['dateName'] . ' AS start')
                ->where($query->expr()->andX(
                    $query->expr()->gte($eventType['dateName'], ':start'),
                    $query->expr()->lte($eventType['dateName'], ':end')
                ));

            $results = $query->execute()->fetchAll();

            // We need to convert the date to a ISO8601 compliant string
            foreach ($results as &$object) {
                $date = new DateTimeHelper($object['start']);
                $object['start'] = $date->toLocalString(\DateTime::ISO8601);
                $object['url']   = $router->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $object['page_id']), true);
                $object['attr']  = 'data-toggle="ajax"';
                $object['description'] = $this->translator->trans('mautic.page.event.' . $eventKey . '.description', array('%page%' => $object['title']));
                $object['title'] = $this->translator->trans('mautic.page.event.' . $eventKey, array('%page%' => $object['title']));
            }

            $event->addEvents($results);
        }
    }
}