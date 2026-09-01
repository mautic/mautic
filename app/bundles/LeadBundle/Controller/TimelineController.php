<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TimelineController extends CommonController
{
    use LeadAccessTrait;
    use LeadDetailsTrait;

    #[Route(
        '/s/contacts/timeline/{leadId}/{page}',
        name: 'mautic_contacttimeline_action',
        requirements: ['leadId' => '\d+', 'page' => '\d+'],
        defaults: ['page' => 0],
        priority: -691
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
            $session->set('mautic.lead.'.$leadId.'.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.lead.'.$leadId.'.timeline.orderby'),
            $session->get('mautic.lead.'.$leadId.'.timeline.orderbydir'),
        ];

        $events = $this->getEngagements($lead, $filters, $order, $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'lead'   => $lead,
                    'page'   => $page,
                    'events' => $events,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'leadTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => '@MauticLead/Timeline/_list.html.twig',
            ]
        );
    }

    #[Route(
        '/s/plugin/{integration}/timeline/{page}',
        name: 'mautic_plugin_timeline_index',
        requirements: ['integration' => \Symfony\Component\Routing\Requirement\Requirement::CATCH_ALL, 'page' => '\d+'],
        defaults: ['page' => 0],
        priority: -680
    )]
    public function pluginIndexAction(Request $request, $integration, int $page = 1): Response
    {
        $limit = 25;
        $leads = $this->checkAllAccess('view', $limit);

        if ($leads instanceof Response) {
            return $leads;
        }

        $this->setListFilters();

        $session = $request->getSession();
        if ('POST' === $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->all()['includeEvents'] ?? []),
                'excludeEvents' => InputHelper::clean($request->request->all()['excludeEvents'] ?? []),
            ];
            $session->set('mautic.plugin.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.plugin.timeline.orderby'),
            $session->get('mautic.plugin.timeline.orderbydir'),
        ];

        // get all events grouped by lead
        $events = $this->getAllEngagements($leads, $filters, $order, $page, $limit);

        $str = $request->server->get('QUERY_STRING');
        parse_str($str, $query);

        $tmpl = 'table';
        if (array_key_exists('from', $query) && 'iframe' === $query['from']) {
            $tmpl = 'list';
        }
        if (array_key_exists('tmpl', $query)) {
            $tmpl = $query['tmpl'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'leads'       => $leads,
                    'page'        => $page,
                    'events'      => $events,
                    'integration' => $integration,
                    'tmpl'        => (!$request->isXmlHttpRequest()) ? 'index' : '',
                    'newCount'    => (array_key_exists('count', $query) && $query['count']) ? $query['count'] : 0,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'pluginTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => sprintf('@MauticLead/Timeline/plugin_%s.html.twig', $tmpl),
            ]
        );
    }

    #[Route(
        '/s/plugin/{integration}/timeline/view/{leadId}/{page}',
        name: 'mautic_plugin_timeline_view',
        requirements: ['integration' => \Symfony\Component\Routing\Requirement\Requirement::CATCH_ALL, 'leadId' => '\d+', 'page' => '\d+'],
        defaults: ['page' => 0],
        priority: -681
    )]
    public function pluginViewAction(Request $request, $integration, $leadId, int $page = 1): Response
    {
        if (empty($leadId)) {
            return $this->notFound();
        }

        $lead = $this->checkLeadAccess($leadId, 'view', true, $integration);
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
            $session->set('mautic.plugin.timeline.'.$leadId.'.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.plugin.timeline.'.$leadId.'.orderby'),
            $session->get('mautic.plugin.timeline.'.$leadId.'.orderbydir'),
        ];

        $events = $this->getEngagements($lead, $filters, $order, $page);

        $str = $request->server->get('QUERY_STRING');
        parse_str($str, $query);

        $tmpl = 'table';
        if (array_key_exists('from', $query) && 'iframe' === $query['from']) {
            $tmpl = 'list';
        }
        if (array_key_exists('tmpl', $query)) {
            $tmpl = $query['tmpl'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'lead'        => $lead,
                    'page'        => $page,
                    'integration' => $integration,
                    'events'      => $events,
                    'newCount'    => (array_key_exists('count', $query) && $query['count']) ? $query['count'] : 0,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'pluginTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => sprintf('@MauticLead/Timeline/plugin_%s.html.twig', $tmpl),
            ]
        );
    }

    #[Route(
        '/s/contacts/timeline/batchExport/{leadId}',
        name: 'mautic_contact_timeline_export_action',
        requirements: ['leadId' => '\d+'],
        priority: -692
    )]
    public function batchExportAction(Request $request, DateHelper $dateHelper, ExportHelper $exportHelper, $leadId): Response
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
            $session->set('mautic.lead.'.$leadId.'.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.lead.'.$leadId.'.timeline.orderby'),
            $session->get('mautic.lead.'.$leadId.'.timeline.orderbydir'),
        ];

        $dataType = $request->get('filetype', 'csv');

        $resultsCallback = function (array $event) use ($dateHelper): array {
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

        $results    = $this->getEngagements($lead, $filters, $order, 1, 200);
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

            $items = $this->getEngagements($lead, $filters, $order, $loop + 1, 200);

            $this->doctrine->getManager()->clear();

            ++$loop;
        }

        return $this->exportResultsAs($toExport, $dataType, 'contact_timeline', $exportHelper);
    }
}
