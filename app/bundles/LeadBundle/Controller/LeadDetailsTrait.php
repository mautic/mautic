<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\RequestStack;

trait LeadDetailsTrait
{
    private ?RequestStack $requestStack = null;

    /**
     * @param int $page
     */
    protected function getAllEngagements(array $leads, array $filters = null, array $orderBy = null, $page = 1, $limit = 25): array
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if (null == $filters) {
            $filters = $session->get(
                'mautic.plugin.timeline.filters',
                [
                    'search'        => '',
                    'includeEvents' => [],
                    'excludeEvents' => [],
                ]
            );
        }

        if (null == $orderBy) {
            if (!$session->has('mautic.plugin.timeline.orderby')) {
                $session->set('mautic.plugin.timeline.orderby', 'timestamp');
                $session->set('mautic.plugin.timeline.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.plugin.timeline.orderby'),
                $session->get('mautic.plugin.timeline.orderbydir'),
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
                $event['leadName']  = $lead->getName() ?: $lead->getEmail();
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
     * Makes sure that the event filter array is in the right format.
     *
     * @param mixed $filters
     *
     * @return array
     *
     * @throws InvalidArgumentException if not an array
     */
    public function sanitizeEventFilter($filters)
    {
        if (!is_array($filters)) {
            throw new \InvalidArgumentException('filters parameter must be an array');
        }

        if (!isset($filters['search'])) {
            $filters['search'] = '';
        }

        if (!isset($filters['includeEvents'])) {
            $filters['includeEvents'] = [];
        }

        if (!isset($filters['excludeEvents'])) {
            $filters['excludeEvents'] = [];
        }

        return $filters;
    }

    private function cmp($a, $b): int
    {
        return $b['timestamp'] <=> $a['timestamp'];
    }

    /**
     * Get a list of places for the lead based on IP location.
     */
    protected function getPlaces(Lead $lead): array
    {
        // Get Places from IP addresses
        $places = [];
        if ($lead->getIpAddresses()->count() > 0) {
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
     * @return mixed[]
     */
    protected function getEngagementData(Lead $lead, \DateTime $fromDate = null, \DateTime $toDate = null): array
    {
        $translator = $this->translator;

        if (null == $fromDate) {
            $fromDate = new \DateTime('first day of this month 00:00:00');
            $fromDate->modify('-6 months');
        }
        if (null == $toDate) {
            $toDate = new \DateTime();
        }

        $lineChart  = new LineChart(null, $fromDate, $toDate);
        $chartQuery = new ChartQuery($this->doctrine->getConnection(), $fromDate, $toDate);

        /** @var LeadModel $model */
        $model       = $this->getModel('lead');
        $engagements = $model->getEngagementCount($lead, $fromDate, $toDate, 'm', $chartQuery);
        $lineChart->setDataset($translator->trans('mautic.lead.graph.line.all_engagements'), $engagements['byUnit']);

        $pointStats = $chartQuery->fetchSumTimeData('lead_points_change_log', 'date_added', ['lead_id' => $lead->getId()], 'delta');
        $lineChart->setDataset($translator->trans('mautic.lead.graph.line.points'), $pointStats);

        return $lineChart->render();
    }

    /**
     * @return mixed[]
     */
    protected function getAuditlogs(Lead $lead, array $filters = null, array $orderBy = null, int $page = 1, int $limit = 25): array
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

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
                $session->set('mautic.lead.'.$lead->getId().'.auditlog.orderby', 'al.dateAdded');
                $session->set('mautic.lead.'.$lead->getId().'.auditlog.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.lead.'.$lead->getId().'.auditlog.orderby'),
                $session->get('mautic.lead.'.$lead->getId().'.auditlog.orderbydir'),
            ];
        }

        // Audit Log
        /** @var AuditLogModel $auditlogModel */
        $auditlogModel = $this->getModel('core.auditlog');
        /** @var AuditLogRepository $repo */
        $repo     = $auditlogModel->getRepository();
        $logCount = $repo->getAuditLogsCount($lead, $filters);
        $logs     = $repo->getAuditLogs($lead, $filters, $orderBy, $page, $limit);

        $logEvents = array_map(fn ($l): array => [
            'eventType'       => $l['action'],
            'eventLabel'      => $l['userName'],
            'timestamp'       => $l['dateAdded'],
            'details'         => $l['details'],
            'contentTemplate' => '@MauticLead/Auditlog/details.html.twig',
        ], $logs);

        $types = [
            'delete'     => $this->translator->trans('mautic.lead.event.delete'),
            'create'     => $this->translator->trans('mautic.lead.event.create'),
            'identified' => $this->translator->trans('mautic.lead.event.identified'),
            'ipadded'    => $this->translator->trans('mautic.lead.event.ipadded'),
            'merge'      => $this->translator->trans('mautic.lead.event.merge'),
            'update'     => $this->translator->trans('mautic.lead.event.update'),
        ];

        return [
            'events'   => $logEvents,
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => $types,
            'total'    => $logCount,
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => ceil($logCount / $limit),
        ];
    }

    /**
     * @param int $page
     * @param int $limit
     */
    protected function getEngagements(Lead $lead, array $filters = null, array $orderBy = null, $page = 1, $limit = 25): array
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

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
     * Get an array with engagements and points of a contact.
     */
    protected function getStatsCount(Lead $lead, \DateTime $fromDate = null, \DateTime $toDate = null): array
    {
        if (null == $fromDate) {
            $fromDate = new \DateTime('first day of this month 00:00:00');
            $fromDate->modify('-6 months');
        }
        if (null == $toDate) {
            $toDate = new \DateTime();
        }

        /** @var LeadModel $model */
        $model       = $this->getModel('lead');
        $chartQuery  = new ChartQuery($this->doctrine->getConnection(), $fromDate, $toDate);

        $engagements = $model->getEngagementCount($lead, $fromDate, $toDate, 'm', $chartQuery);
        $pointStats  = $chartQuery->fetchSumTimeData('lead_points_change_log', 'date_added', ['lead_id' => $lead->getId()], 'delta');

        return [
            'engagements' => $engagements,
            'points'      => $pointStats,
        ];
    }

    /**
     * Get an array to create company's engagements graph.
     *
     * @param array $contacts
     */
    protected function getCompanyEngagementData($contacts): array
    {
        $engagements = [0, 0, 0, 0, 0, 0];
        $points      = [0, 0, 0, 0, 0, 0];
        foreach ($contacts as $contact) {
            /** @var LeadModel $model */
            $model = $this->getModel('lead.lead');

            if (!isset($contact['lead_id'])) {
                continue;
            }

            $lead = $model->getEntity($contact['lead_id']);
            $model->getRepository()->refetchEntity($lead);
            if (!$lead instanceof Lead) {
                continue;
            }
            $engagementsData = $this->getStatsCount($lead);

            $engagements = array_map(fn ($a, $b) => $a + $b, $engagementsData['engagements']['byUnit'], $engagements);
            $points      = array_map(fn ($points_first_user, $points_second_user) => $points_first_user + $points_second_user, $engagementsData['points'], $points);
        }

        return [
            'engagements' => $engagements,
            'points'      => $points,
        ];
    }

    /**
     * Get company graph for points and engagements.
     *
     * @return array<string, mixed>
     */
    protected function getCompanyEngagementsForGraph($contacts): array
    {
        $graphData  = $this->getCompanyEngagementData($contacts);
        $translator = $this->translator;

        $fromDate = new \DateTime('first day of this month 00:00:00');
        $fromDate->modify('-6 months');

        $toDate = new \DateTime();

        $lineChart  = new LineChart(null, $fromDate, $toDate);

        $lineChart->setDataset($translator->trans('mautic.lead.graph.line.all_engagements'), $graphData['engagements']);

        $lineChart->setDataset($translator->trans('mautic.lead.graph.line.points'), $graphData['points']);

        return $lineChart->render();
    }

    protected function getScheduledCampaignEvents(Lead $lead): array
    {
        // Upcoming events from Campaign Bundle
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->doctrine->getManager()->getRepository(\Mautic\CampaignBundle\Entity\LeadEventLog::class);

        return $leadEventLogRepository->getUpcomingEvents(
            [
                'lead'      => $lead,
                'eventType' => ['action', 'condition'],
            ]
        );
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setRequestStackLeadDetailsTrait(?RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }
}
