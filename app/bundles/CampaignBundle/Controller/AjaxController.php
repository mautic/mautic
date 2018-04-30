<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\EventLogModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateConnectionsAction(Request $request)
    {
        $session        = $this->get('session');
        $campaignId     = InputHelper::clean($request->query->get('campaignId'));
        $canvasSettings = $request->request->get('canvasSettings', [], true);
        if (empty($campaignId)) {
            $dataArray = ['success' => 0];
        } else {
            $session->set('mautic.campaign.'.$campaignId.'.events.canvassettings', $canvasSettings);

            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     */
    protected function updateScheduledCampaignEventAction(Request $request)
    {
        $eventId      = (int) $request->request->get('eventId');
        $contactId    = (int) $request->request->get('contactId');
        $newDate      = InputHelper::clean($request->request->get('date'));
        $originalDate = InputHelper::clean($request->request->get('originalDate'));

        $dataArray = ['success' => 0, 'date' => $originalDate];

        if (!empty($eventId) && !empty($contactId) && !empty($newDate)) {
            if ($log = $this->getContactEventLog($eventId, $contactId)) {
                $newDate = new \DateTime($newDate);

                if ($newDate >= new \DateTime()) {
                    $log->setTriggerDate($newDate);

                    /** @var EventLogModel $logModel */
                    $logModel = $this->getModel('campaign.event_log');
                    $logModel->saveEntity($log);

                    $dataArray = [
                        'success' => 1,
                        'date'    => $newDate->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Format the date to match the view
        $dataArray['formattedDate'] = $this->get('mautic.helper.template.date')->toFull($dataArray['date']);

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function cancelScheduledCampaignEventAction(Request $request)
    {
        $dataArray = ['success' => 0];

        $eventId   = (int) $request->request->get('eventId');
        $contactId = (int) $request->request->get('contactId');
        if (!empty($eventId) && !empty($contactId)) {
            if ($log = $this->getContactEventLog($eventId, $contactId)) {
                $log->setIsScheduled(false);

                /** @var EventLogModel $logModel */
                $logModel = $this->getModel('campaign.event_log');
                $logModel->saveEntity($log);

                $dataArray = ['success' => 1];
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param $eventId
     * @param $contactId
     *
     * @return LeadEventLog|null
     */
    protected function getContactEventLog($eventId, $contactId)
    {
        $contact = $this->getModel('lead')->getEntity($contactId);
        if ($contact) {
            if ($this->get('mautic.security')->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
                /** @var EventLogModel $logModel */
                $logModel = $this->getModel('campaign.event_log');

                /** @var LeadEventLog $log */
                $log = $logModel->getRepository()
                    ->findOneBy(
                        [
                            'lead'  => $contactId,
                            'event' => $eventId,
                        ]
                    );

                if ($log && ($log->getTriggerDate() > new \DateTime())) {
                    return $log;
                }
            }
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    protected function toggleCampaignTabDataAction(Request $request)
    {
        $events     = [];
        $mode       = $request->request->get('mode');
        $campaignId = $request->request->get('campaignId');
        $fromDate   = $request->request->get('fromDate');
        $toDate     = $request->request->get('toDate');

        if ('byDate' === $mode) {
            // prepare date values to pass in to event query
            $dateFrom = empty($fromDate) ?
                new \DateTime('-30 days midnight')
                :
                new \DateTime($fromDate);

            $dateTo                       = empty($toDate)
                ?
                new \DateTime('midnight')
                :
                new \DateTime($toDate);
            $dateRangeValues['date_from'] = $dateFrom;
            $dateRangeValues['date_to']   = $dateTo;
        } else {
            $dateRangeValues = [];
        }

        /** @var LeadEventLogRepository $eventLogRepo */
        $eventLogRepo      = $this->getDoctrine()->getManager()->getRepository('MauticCampaignBundle:LeadEventLog');
        $campaignModel     = $this->container->get('mautic.model.factory')->getModel('campaign');
        $events            = $campaignModel->getEventRepository()->getCampaignEvents((int) $campaignId);
        $leadCount         = $campaignModel->getRepository()->getCampaignLeadCount(
            (int) $campaignId,
            null,
            [],
            $dateRangeValues
        );
        $campaignLogCounts = $eventLogRepo->getCampaignLogCounts((int) $campaignId, false, false, $dateRangeValues);
        $sortedEvents      = [
            'decision'  => [],
            'action'    => [],
            'condition' => [],
        ];

        foreach ($events as $event) {
            $event['logCount']   =
            $event['percent']    =
            $event['yesPercent'] =
            $event['noPercent']  = 0;
            $event['leadCount']  = $leadCount;

            if (isset($campaignLogCounts[$event['id']])) {
                $event['logCount'] = array_sum($campaignLogCounts[$event['id']]);

                if ($leadCount) {
                    $event['percent']    = round(($event['logCount'] / $leadCount) * 100, 1);
                    $event['yesPercent'] = round(($campaignLogCounts[$event['id']][1] / $leadCount) * 100, 1);
                    $event['noPercent']  = round(($campaignLogCounts[$event['id']][0] / $leadCount) * 100, 1);
                }
            }

            $sortedEvents[$event['eventType']][] = $event;
        }
        //return $this->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events]);

        $decisions  = trim(
            $this->renderView('MauticCampaignBundle:Campaign:events.html.php', ['events' => $sortedEvents['decision']])
        );
        $actions    = trim(
            $this->renderView('MauticCampaignBundle:Campaign:events.html.php', ['events' => $sortedEvents['action']])
        );
        $conditions = trim(
            $this->renderView('MauticCampaignBundle:Campaign:events.html.php', ['events' => $sortedEvents['condition']])
        );

        $finalHTML = ['decisions' => $decisions, 'actions' => $actions, 'conditions' => $conditions];

        $response =  new Response(json_encode($finalHTML));

        return $response;
    }
}
