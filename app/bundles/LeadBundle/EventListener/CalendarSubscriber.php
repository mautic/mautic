<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class CalendarSubscriber.
 */
class CalendarSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CalendarEvents::CALENDAR_ON_GENERATE => ['onCalendarGenerate', 0],
        ];
    }

    /**
     * Adds events to the calendar.
     *
     * @param CalendarGeneratorEvent $event
     */
    public function onCalendarGenerate(CalendarGeneratorEvent $event)
    {
        $dates = $event->getDates();

        // Lead Notes
        $query = $this->em->getConnection()->createQueryBuilder();
        $query->select('ln.lead_id, l.firstname, l.lastname, ln.date_time AS start, ln.text AS description, ln.type')
            ->from(MAUTIC_TABLE_PREFIX.'lead_notes', 'ln')
            ->leftJoin('ln', MAUTIC_TABLE_PREFIX.'leads', 'l', 'ln.lead_id = l.id')
            ->where($query->expr()->andX(
                $query->expr()->gte('ln.date_time', ':start'),
                $query->expr()->lte('ln.date_time', ':end')
            ))
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(100);

        $results = $query->execute()->fetchAll();

        // We need to convert the date to a ISO8601 compliant string
        foreach ($results as &$object) {
            if ($object['firstname'] || $object['lastname']) {
                $leadName = $object['firstname'].' '.$object['lastname'];
            } else {
                $leadName = $this->translator->trans('mautic.lead.lead.anonymous');
            }
            $date                  = new DateTimeHelper($object['start']);
            $object['start']       = $date->toLocalString(\DateTime::ISO8601);
            $object['url']         = $this->router->generate('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $object['lead_id']], true);
            $object['attr']        = 'data-toggle="ajax"';
            $object['description'] = strip_tags(html_entity_decode($object['description']));

            switch ($object['type']) {
                default:
                case 'general':
                    $icon = 'fa-file-text';
                    break;
                case 'email':
                    $icon = 'fa-send';
                    break;
                case 'call':
                    $icon = 'fa-phone';
                    break;
                case 'meeting':
                    $icon = 'fa-group';
                    break;
            }

            $object['iconClass'] = 'fa fa-fw '.$icon;
            $object['title']     = $leadName;
            //$object['title'] .= ' (' . $this->translator->trans('mautic.lead.note.type.' . $object['type']) . ')';
        }

        $event->addEvents($results);
    }
}
