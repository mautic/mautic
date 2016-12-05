<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

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
            //CalendarEvents::CALENDAR_ON_GENERATE => array('onCalendarGenerate', 0)
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

        $query = $this->em->getConnection()->createQueryBuilder();
        $query->select('fs.referer AS url, f.name AS title, fs.date_submitted AS start')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
            ->leftJoin('fs', MAUTIC_TABLE_PREFIX.'forms', 'f', 'fs.form_id = f.id')
            ->where($query->expr()->andX(
                $query->expr()->gte('fs.date_submitted', ':start'),
                $query->expr()->lte('fs.date_submitted', ':end')
            ))
            ->setParameter('start', $dates['start_date'])
            ->setParameter('end', $dates['end_date'])
            ->setFirstResult(0)
            ->setMaxResults(5);

        $results = $query->execute()->fetchAll();

        // We need to convert the date to a ISO8601 compliant string
        foreach ($results as &$object) {
            $date            = new DateTimeHelper($object['start']);
            $object['start'] = $date->toLocalString(\DateTime::ISO8601);
            $object['title'] = $this->translator->trans('mautic.form.event.submission', ['%form%' => $object['title']]);
        }

        $event->addEvents($results);
    }
}
