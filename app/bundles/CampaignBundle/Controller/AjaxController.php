<?php

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Model\EventLogModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private DateHelper $dateHelper;

    private EventLogModel $eventLogModel;

    private LeadModel $leadModel;

    private LeadEventLogRepository $leadEventLogRepository;

    #[Required]
    public function autowireCampaignAjaxController(
        DateHelper $dateHelper,
        EventLogModel $eventLogModel,
        LeadModel $leadModel,
        LeadEventLogRepository $leadEventLogRepository,
    ): void {
        $this->dateHelper             = $dateHelper;
        $this->eventLogModel          = $eventLogModel;
        $this->leadModel              = $leadModel;
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    public function updateConnectionsAction(Request $request): JsonResponse
    {
        $session        = $request->getSession();
        $campaignId     = InputHelper::clean($request->query->get('campaignId'));
        $canvasSettings = $request->request->all()['canvasSettings'] ?? [];
        if (empty($campaignId)) {
            $dataArray = ['success' => 0];
        } else {
            $session->set('mautic.campaign.'.$campaignId.'.events.canvassettings', $canvasSettings);

            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function updateScheduledCampaignEventAction(Request $request): JsonResponse
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
                    $log->setTriggerDate($newDate, 'Manual date change via AJAX');
                    $this->eventLogModel->saveEntity($log);

                    $dataArray = [
                        'success' => 1,
                        'date'    => $newDate->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Format the date to match the view
        $dataArray['formattedDate'] = $this->dateHelper->toFull($dataArray['date']);

        return $this->sendJsonResponse($dataArray);
    }

    public function cancelScheduledCampaignEventAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];

        $eventId   = (int) $request->request->get('eventId');
        $contactId = (int) $request->request->get('contactId');
        if (!empty($eventId) && !empty($contactId)) {
            if ($log = $this->getContactEventLog($eventId, $contactId)) {
                $log->setIsScheduled(false);
                $metadata           = $log->getMetadata();
                $metadata['errors'] = $this->translator->trans(
                    'mautic.campaign.event.cancelled.time',
                    ['%date%' => $log->getTriggerDate()->format('Y-m-d H:i:s')]
                );
                $log->setMetadata($metadata);
                $this->leadEventLogRepository->saveEntity($log);

                $dataArray = ['success' => 1];
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return LeadEventLog|null
     */
    private function getContactEventLog(int $eventId, int $contactId)
    {
        $contact = $this->leadModel->getEntity($contactId);
        if ($contact) {
            if ($this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
                /** @var LeadEventLog $log */
                $log = $this->leadEventLogRepository
                                ->findOneBy(
                                    [
                                        'lead'  => $contactId,
                                        'event' => $eventId,
                                    ],
                                    ['dateTriggered' => 'desc']
                                );

                if ($log && ($log->getTriggerDate() > new \DateTime())) {
                    return $log;
                }
            }
        }

        return null;
    }
}
