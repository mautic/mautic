<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGmailBundle\Controller;

use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

trait DetailsTrait
{

    /**
     * @param array      $leads
     * @param array|null $filters
     * @param array|null $orderBy
     * @param int        $page
     *
     * @return array
     */
    protected function getAllEngagements(array $leads, array $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
        $session = $this->get('session');

        if (null == $filters) {
            $filters = $session->get(
                'mautic.gmail.timeline.filters',
                [
                    'search'        => '',
                    'includeEvents' => [],
                    'excludeEvents' => []
                ]
            );
        }

        if (null == $orderBy) {
            if (!$session->has('mautic.gmail.timeline.orderby')) {
                $session->set('mautic.gmail.timeline.orderby', 'timestamp');
                $session->set('mautic.gmail.timeline.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.gmail.timeline.orderby'),
                $session->get('mautic.gmail.timeline.orderbydir')
            ];
        }

        // prepare result object
        $result = [
            'events'   => [],
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => [],
            'total'    => 0,
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => 0
        ];

        // get events for each contact
        foreach ($leads as /** @var LeadModel $lead */ $lead) {
          //  if (!$lead->getEmail()) continue; // discard contacts without email

            /** @var LeadModel $model */
            $model = $this->getModel('lead');
            $engagements = $model->getEngagements($lead, $filters, $orderBy, $page, $limit);
            $events = $engagements['events'];
            $types = $engagements['types'];

            // inject lead into events
            foreach($events as &$event){
                $event['leadId'] = $lead->getId();
                $event['leadEmail'] = $lead->getEmail();
                $event['leadName'] = $lead->getName() ? $lead->getName() : $lead->getEmail();
            }

            $result['events'] = array_merge($result['events'], $events);
            $result['types'] = array_merge($result['types'], $types);
            $result['total'] += $engagements['total'];
        }

        $result['maxPages'] = ($limit<=0)? 1 : round(ceil($result['total'] / $limit));

        usort($result['events'], array($this, 'cmp')); // sort events by

        // now all events are merged, let's limit to   $limit
        array_splice($result['events'], $limit);

        $result['total'] = count($result['events']);

        return $result;
    }

    private function cmp($a, $b)
    {
        if ($a['timestamp'] === $b['timestamp']) {
            return 0;
        }
        return ($a['timestamp'] < $b['timestamp']) ? +1 : -1;
    }
}