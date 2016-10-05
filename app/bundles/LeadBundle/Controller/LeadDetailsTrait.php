<?php
/**
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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

trait LeadDetailsTrait
{
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
     *
     * @return array
     */
    protected function getEngagements(Lead $lead, array $filters = null, array $orderBy = null, $page = 1)
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

        return $model->getEngagements($lead, $filters, $orderBy, $page);
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

        return $leadEventLogRepository->getUpcomingEvents(['lead' => $lead, 'scheduled' => 1, 'eventType' => 'action']);
    }
}
