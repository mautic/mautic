<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CampaignBundle\EventListener;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CalendarSubscriber
 *
 * @package Mautic\CampaignBundle\EventListener
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

        $commonSelect = 'cl.campaign_id, c.name AS campaign_name, l.firstname, l.lastname, ce.type AS event_type, ce.name as event_name';
        $date1 = 'cl.date_triggered';
        $date2 = 'cl.trigger_date';

        $query = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
        $query->select($commonSelect . ', ' . $date1 . ' AS start')
            ->from(MAUTIC_TABLE_PREFIX . 'campaign_lead_event_log', 'cl')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX . 'campaigns', 'c', 'cl.campaign_id = c.id')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'cl.lead_id = l.id')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX . 'campaign_events', 'ce', 'cl.event_id = ce.id')
            ->where($query->expr()->andX(
                $query->expr()->gte($date1, ':start'),
                $query->expr()->lte($date1, ':end')
            ))
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(50);
        $results = $query->execute()->fetchAll();

        // We need to convert the date to a ISO8601 compliant string
        foreach ($results as &$object) {
            if ($object['firstname'] || $object['lastname']) {
                $leadName = $object['firstname'] . ' ' . $object['lastname'];
            } else {
                $leadName = $this->translator->trans('mautic.lead.lead.anonymous');
            }
            $date = new DateTimeHelper($object['start']);
            $object['start'] = $date->toLocalString(\DateTime::ISO8601);
            $object['url']   = $router->generate('mautic_campaign_action', array('objectAction' => 'view', 'objectId' => $object['campaign_id']), true);
            $object['attr']  = 'data-toggle="ajax"';
            $object['description'] = $this->translator->trans('mautic.campaign.event.triggered.description', array('%campaign%' => $object['campaign_name'], '%lead%' => $leadName));
            $object['title'] = $this->translator->trans('mautic.campaign.event.triggered', array('%event%' => $object['event_name']));
        }

        $event->addEvents($results);
    }
}