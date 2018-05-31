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
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateCampaignTagsAction(Request $request)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel   = $this->getModel('campaign');
        $post            = $request->request->get('campaign_tags', [], true);
        $campaign        = $campaignModel->getEntity((int) $post['id']);
        $updatedTags     = (!empty($post['tags']) && is_array($post['tags'])) ? $post['tags'] : [];
        $data            = ['success' => 0];

        if ($campaign !== null && $this->get('mautic.security')->hasEntityAccess('campaign:campaigns:editown', 'campaign:campaigns:editother', $campaign->getPermissionUser())) {
            $campaignModel->setTags($campaign, $updatedTags, true);

            /** @var \Doctrine\ORM\PersistentCollection $campaignTags */
            $campaignTags    = $campaign->getTags();
            $campaignTagKeys = $campaignTags->getKeys();

            // Get an updated list of tags
            $tags       = $campaignModel->getTagRepository()->getSimpleList(null, [], 'tag');
            $tagOptions = '';

            foreach ($tags as $tag) {
                $selected = (in_array($tag['label'], $campaignTagKeys)) ? ' selected="selected"' : '';
                $tagOptions .= '<option'.$selected.' value="'.$tag['value'].'">'.$tag['label'].'</option>';
            }

            $data['success'] = 1;
            $data['tags']    = $tagOptions;
        }

        return $this->sendJsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function addCampaignTagsAction(Request $request)
    {
        $tags = $request->request->get('tags');
        $tags = json_decode($tags, true);

        if (is_array($tags)) {
            $campaignModel = $this->getModel('campaign');
            $newTags       = [];

            foreach ($tags as $tag) {
                if (!is_numeric($tag)) {
                    $newTags[] = $campaignModel->getTagRepository()->getTagByNameOrCreateNewOne($tag);
                }
            }

            if (!empty($newTags)) {
                $campaignModel->getTagRepository()->saveEntities($newTags);
            }

            // Get an updated list of tags
            $allTags    = $campaignModel->getTagRepository()->getSimpleList(null, [], 'tag');
            $tagOptions = '';

            foreach ($allTags as $tag) {
                $selected = (in_array($tag['value'], $tags) || in_array($tag['label'], $tags)) ? ' selected="selected"' : '';
                $tagOptions .= '<option'.$selected.' value="'.$tag['value'].'">'.$tag['label'].'</option>';
            }

            $data = [
                'success' => 1,
                'tags'    => $tagOptions,
            ];
        } else {
            $data = ['success' => 0];
        }

        return $this->sendJsonResponse($data);
    }
}
