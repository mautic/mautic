<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuditlogController extends CommonController
{
    use LeadAccessTrait;
    use LeadDetailsTrait;

    #[Route(
        '/s/contacts/auditlog/{leadId}/{page}',
        name: 'mautic_contact_auditlog_action',
        requirements: ['leadId' => '\d+', 'page' => '\d+'],
        defaults: ['page' => 0],
        priority: -693
    )]
    public function indexAction(Request $request, $leadId, int $page = 1): Response
    {
        if (empty($leadId)) {
            $this->throwAccessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        $this->setListFilters();

        $session = $request->getSession();
        if ('POST' === $request->getMethod() && $request->request->has('search')) {
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

    #[Route(
        '/s/contacts/auditlog/batchExport/{leadId}',
        name: 'mautic_contact_auditlog_export_action',
        requirements: ['leadId' => '\d+'],
        priority: -694
    )]
    public function batchExportAction(Request $request, DateHelper $dateHelper, ExportHelper $exportHelper, $leadId): Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (empty($leadId)) {
            $this->throwAccessDenied();
        }

        $lead = $this->checkLeadAccess($leadId, 'view');
        if ($lead instanceof Response) {
            return $lead;
        }

        if (!$this->security->isGranted('report:export:enable', 'MATCH_ONE')) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();
        if ('POST' === $request->getMethod() && $request->request->has('search')) {
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

        $resultsCallback = function (array $event) use ($dateHelper): array {
            $userName = $event['userName'] ?? $event['eventType'];
            if (is_array($userName)) {
                $userName = $userName['label'];
            }

            return [
                'userName'       => $userName,
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
