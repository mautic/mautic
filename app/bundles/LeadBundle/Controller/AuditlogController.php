<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditlogController extends CommonController
{
    use LeadAccessTrait;
    use LeadDetailsTrait;

    public function indexAction(Request $request, $leadId, $page = 1)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $this->setListFilters();

        $session = $request->getSession();
        if ('POST' == $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->all()['includeEvents'] ?? []),
                'excludeEvents' => InputHelper::clean($request->request->all()['excludeEvents'] ?? []),
            ];
            $session->set('mautic.lead.'.$leadId.'.auditlog.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.lead.'.$leadId.'.auditlog.orderby'),
            $session->get('mautic.lead.'.$leadId.'.auditlog.orderbydir'),
        ];

        $events = $this->getAuditlogs($lead, $filters, $order, $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'lead'                   => $lead,
                    'page'                   => $page,
                    'events'                 => $events,
                    'enableExportPermission' => $this->security->isAdmin() || $this->security->isGranted('report:export:enable', 'MATCH_ONE'),
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'leadAuditlog',
                    'auditLogCount' => $events['total'],
                ],
                'contentTemplate' => '@MauticLead/Auditlog/_list.html.twig',
            ]
        );
    }

    /**
     * @return array|Response
     */
    public function batchExportAction(Request $request, DateHelper $dateHelper, ExportHelper $exportHelper, $leadId)
    {
        if (empty($leadId)) {
            return $this->accessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        if (!$this->security->isGranted('report:export:enable', 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();
        if ('POST' == $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->all()['includeEvents'] ?? []),
                'excludeEvents' => InputHelper::clean($request->request->all()['excludeEvents'] ?? []),
            ];
            $session->set('mautic.lead.'.$leadId.'.auditlog.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.lead.'.$leadId.'.auditlog.orderby'),
            $session->get('mautic.lead.'.$leadId.'.auditlog.orderbydir'),
        ];

        $dataType = $request->get('filetype', 'csv');

        $resultsCallback = function ($event) use ($dateHelper): array {
            $eventLabel = $event['eventLabel'] ?? $event['eventType'];
            if (is_array($eventLabel)) {
                $eventLabel = $eventLabel['label'];
            }

            return [
                'eventName'      => $eventLabel,
                'eventType'      => $event['eventType'] ?? '',
                'eventTimestamp' => $dateHelper->toText($event['timestamp'], 'local', 'Y-m-d H:i:s', true),
            ];
        };

        $results    = $this->getAuditlogs($lead, $filters, $order, 1, 200);
        $count      = $results['total'];
        $items      = $results['events'];
        $iterations = ceil($count / 200);
        $loop       = 1;

        // Max of 50 iterations for 10K result export
        if ($iterations > 50) {
            $iterations = 50;
        }

        $toExport = [];

        while ($loop <= $iterations) {
            if (is_callable($resultsCallback)) {
                foreach ($items as $item) {
                    $toExport[] = $resultsCallback($item);
                }
            } else {
                foreach ($items as $item) {
                    $toExport[] = (array) $item;
                }
            }

            $items = $this->getAuditlogs($lead, $filters, $order, $loop + 1, 200);

            $this->doctrine->getManager()->clear();

            ++$loop;
        }

        return $this->exportResultsAs($toExport, $dataType, 'contact_auditlog', $exportHelper);
    }
}
