<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CalendarSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
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
     */
    public function onCalendarGenerate(CalendarGeneratorEvent $event)
    {
        $dates  = $event->getDates();

        $query = $this->em->getConnection()->createQueryBuilder();
        $query->select('es.email_id, e.subject AS title, COUNT(es.id) AS quantity, es.date_sent AS start, e.plain_text AS description, cat.color, es.lead_id, l.firstname, l.lastname, l.email')
            ->from(MAUTIC_TABLE_PREFIX . 'email_stats', 'es')
            ->leftJoin('es', MAUTIC_TABLE_PREFIX . 'emails', 'e', 'es.email_id = e.id')
            ->leftJoin('e', MAUTIC_TABLE_PREFIX . 'categories', 'cat', 'cat.id = e.category_id AND cat.bundle=:bundle')
            ->leftJoin('es', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = es.lead_id')
            ->where($query->expr()->andX(
                $query->expr()->gte('es.date_sent', ':start'),
                $query->expr()->lte('es.date_sent', ':end')
            ))
            ->groupBy('e.id, es.email_id, e.subject, es.date_sent, e.plain_text, cat.color, es.lead_id, l.firstname, l.lastname, l.email')
            ->setParameter('bundle', 'email')
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(15);

        $results = $query->execute()->fetchAll();

        // We need to convert the date to a ISO8601 compliant string
        foreach ($results as &$object) {
            $date = new DateTimeHelper($object['start']);
            $object['start'] = $date->toLocalString(\DateTime::ISO8601);
            if ($object['email_id']) {
                $object['url']          = $this->router->generate('mautic_email_action', array('objectAction' => 'view', 'objectId' => $object['email_id']), true);
                $object['attr']         = 'data-toggle="ajax"';
                $object['description']  = html_entity_decode($object['description']);
                $object['title']        = $this->translator->trans('mautic.email.event.sent', array('%email%' => $object['title'], '%x%' => $object['quantity']));
            } else {
                if ($object['firstname'] || $object['lastname']) {
                    $contactName = trim($object['firstname'] . ' ' . $object['lastname']);
                } elseif ($object['email']) {
                    $contactName = $object['email'];
                } else {
                    $contactName = $this->translator->trans('mautic.lead.lead.anonymous');
                }
                $details                = $this->translator->trans('mautic.email.event.sent.direct', array('%contact%' => $contactName));
                $object['url']          = $this->router->generate('mautic_contact_action', array('objectAction' => 'view', 'objectId' => $object['lead_id']), true);
                $object['attr']         = 'data-toggle="ajax"';
                $object['title']        = $details;
                $object['description']  = $details;
            }
        }

        $event->addEvents($results);
    }
}
