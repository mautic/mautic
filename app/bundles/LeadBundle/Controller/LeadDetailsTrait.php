<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

trait LeadDetailsTrait
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
                    'excludeEvents' => [],
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
                $session->get('mautic.gmail.timeline.orderbydir'),
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
            'maxPages' => 0,
        ];

        // get events for each contact
        foreach ($leads as $lead) {
            //  if (!$lead->getEmail()) continue; // discard contacts without email

            /** @var LeadModel $model */
            $model       = $this->getModel('lead');
            $engagements = $model->getEngagements($lead, $filters, $orderBy, $page, $limit);
            $events      = $engagements['events'];
            $types       = $engagements['types'];

            // inject lead into events
            foreach ($events as &$event) {
                $event['leadId']    = $lead->getId();
                $event['leadEmail'] = $lead->getEmail();
                $event['leadName']  = $lead->getName() ? $lead->getName() : $lead->getEmail();
            }

            $result['events'] = array_merge($result['events'], $events);
            $result['types']  = array_merge($result['types'], $types);
            $result['total'] += $engagements['total'];
        }

        $result['maxPages'] = ($limit <= 0) ? 1 : round(ceil($result['total'] / $limit));

        usort($result['events'], [$this, 'cmp']); // sort events by

        // now all events are merged, let's limit to   $limit
        array_splice($result['events'], $limit);

        $result['total'] = count($result['events']);

        return $result;
    }

    /**
     * @param $a
     * @param $b
     *
     * @return int
     */
    private function cmp($a, $b)
    {
        if ($a['timestamp'] === $b['timestamp']) {
            return 0;
        }

        return ($a['timestamp'] < $b['timestamp']) ? +1 : -1;
    }

    /**
     * Get a list of places for the lead based on IP location.
     *
     * @param Lead $lead
     *
     * @return array
     */
    protected function getPlaces(Lead $lead)
    {
        // Get Places from IP addresses
        $places = [];
        if ($lead->getIpAddresses()) {
            foreach ($lead->getIpAddresses() as $ip) {
                if ($details = $ip->getIpDetails()) {
                    if (!empty($details['latitude']) && !empty($details['longitude'])) {
                        $name = 'N/A';
                        if (!empty($details['city'])) {
                            $name = $details['city'];
                        } elseif (!empty($details['region'])) {
                            $name = $details['region'];
                        }
                        $place = [
                            'latLng' => [$details['latitude'], $details['longitude']],
                            'name'   => $name,
                        ];
                        $places[] = $place;
                    }
                }
            }
        }

        return $places;
    }

    /**
     * @param Lead           $lead
     * @param \DateTime|null $fromDate
     * @param \DateTime|null $toDate
     *
     * @return mixed
     */
    protected function getEngagementData(Lead $lead, \DateTime $fromDate = null, \DateTime $toDate = null)
    {
        $translator = $this->get('translator');

        if (null == $fromDate) {
            $fromDate = new \DateTime('first day of this month 00:00:00');
            $fromDate->modify('-6 months');
        }
        if (null == $toDate) {
            $toDate = new \DateTime();
        }

        $lineChart  = new LineChart(null, $fromDate, $toDate);
        $chartQuery = new ChartQuery($this->getDoctrine()->getConnection(), $fromDate, $toDate);

        /** @var LeadModel $model */
        $model       = $this->getModel('lead');
        $engagements = $model->getEngagementCount($lead, $fromDate, $toDate, 'm', $chartQuery);
        $lineChart->setDataset($translator->trans('mautic.lead.graph.line.all_engagements'), $engagements['byUnit']);

        $pointStats = $chartQuery->fetchTimeData('lead_points_change_log', 'date_added', ['lead_id' => $lead->getId()]);
        $lineChart->setDataset($translator->trans('mautic.lead.graph.line.points'), $pointStats);

        return $lineChart->render();
    }

    /**
     * @param Lead       $lead
     * @param array|null $filters
     * @param array|null $orderBy
     * @param int        $page
     * @param int        $limit
     *
     * @return array
     */
    protected function getAuditlogs(Lead $lead, array $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
        $session = $this->get('session');

        if (null == $filters) {
            $filters = $session->get(
                'mautic.lead.'.$lead->getId().'.auditlog.filters',
                [
                    'search'        => '',
                    'includeEvents' => [],
                    'excludeEvents' => [],
                ]
            );
        }

        if (null == $orderBy) {
            if (!$session->has('mautic.lead.'.$lead->getId().'.auditlog.orderby')) {
                $session->set('mautic.lead.'.$lead->getId().'.auditlog.orderby', 'timestamp');
                $session->set('mautic.lead.'.$lead->getId().'.auditlog.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.lead.'.$lead->getId().'.auditlog.orderby'),
                $session->get('mautic.lead.'.$lead->getId().'.auditlog.orderbydir'),
            ];
        }

        // Audit Log
        /** @var AuditLogModel $auditlogModel */
        $auditlogModel = $this->getModel('core.auditLog');
        $logs          = $auditlogModel->getLogForObject('lead', $lead->getId(), null, 10, 'lead');
        $logEvents     = array_map(function ($l) {
            return [
                'eventType'       => $this->translator->trans('mautic.lead.event.'.$l['action']),
                'eventLabel'      => $l['userName'],
                'timestamp'       => $l['dateAdded'],
                'details'         => $l['details'],
                'contentTemplate' => 'MauticLeadBundle:Auditlog:details.html.php',
            ];
        }, $logs);

        $types = [
            'delete'     => $this->translator->trans('mautic.lead.event.delete'),
            'create'     => $this->translator->trans('mautic.lead.event.create'),
            'identified' => $this->translator->trans('mautic.lead.event.identified'),
            'ipadded'    => $this->translator->trans('mautic.lead.event.ipadded'),
            'merge'      => $this->translator->trans('mautic.lead.event.merge'),
            'update'     => $this->translator->trans('mautic.lead.event.update'),
        ];

        $auditlog = [
            'types'    => $types,
            'events'   => $logEvents,
            'page'     => 1,
            'maxPages' => 1,
            'total'    => count($logs),
            'filters'  => [
                'search'        => '',
                'includeEvents' => [],
                'excludeEvents' => [],
            ],
        ];

        return $auditlog;
    }

    /**
     * @param Lead       $lead
     * @param array|null $filters
     * @param array|null $orderBy
     * @param int        $page
     * @param int        $limit
     *
     * @return array
     */
    protected function getEngagements(Lead $lead, array $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
        $session = $this->get('session');

        if (null == $filters) {
            $filters = $session->get(
                'mautic.lead.'.$lead->getId().'.timeline.filters',
                [
                    'search'        => '',
                    'includeEvents' => [],
                    'excludeEvents' => [],
                ]
            );
        }

        if (null == $orderBy) {
            if (!$session->has('mautic.lead.'.$lead->getId().'.timeline.orderby')) {
                $session->set('mautic.lead.'.$lead->getId().'.timeline.orderby', 'timestamp');
                $session->set('mautic.lead.'.$lead->getId().'.timeline.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.lead.'.$lead->getId().'.timeline.orderby'),
                $session->get('mautic.lead.'.$lead->getId().'.timeline.orderbydir'),
            ];
        }
        /** @var LeadModel $model */
        $model = $this->getModel('lead');

        return $model->getEngagements($lead, $filters, $orderBy, $page, $limit);
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    protected function getScheduledCampaignEvents(Lead $lead)
    {
        // Upcoming events from Campaign Bundle
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->getDoctrine()->getManager()->getRepository('MauticCampaignBundle:LeadEventLog');

        return $leadEventLogRepository->getUpcomingEvents(
            [
                'lead'      => $lead,
                'eventType' => ['action', 'condition'],
            ]
        );
    }
}
