<?php

namespace Mautic\ChannelBundle\Controller;

use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * @param $eventId
     * @param $contactId
     *
     * @return LeadEventLog|\Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    public function cancelQueuedMessageEventAction(Request $request)
    {
        $dataArray      = ['success' => 0];
        $messageQueueId = (int) $request->request->get('channelId');
        /** @var MessageQueueModel $queueModel */
        $queueModel     = $this->getModel('channel.queue');
        $queuedMessage  = $queueModel->getEntity($messageQueueId);
        if ($queuedMessage) {
            $queuedMessage->setStatus('cancelled');
            $queueModel->saveEntity($queuedMessage);
            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }
}
