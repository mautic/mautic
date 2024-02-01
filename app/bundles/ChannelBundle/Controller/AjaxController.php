<?php

namespace Mautic\ChannelBundle\Controller;

use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    public function cancelQueuedMessageEventAction(Request $request): JsonResponse
    {
        $dataArray      = ['success' => 0];
        $messageQueueId = (int) $request->request->get('channelId');
        $queueModel     = $this->getModel('channel.queue');
        \assert($queueModel instanceof MessageQueueModel);
        $queuedMessage  = $queueModel->getEntity($messageQueueId);
        if ($queuedMessage) {
            $queuedMessage->setStatus('cancelled');
            $queueModel->saveEntity($queuedMessage);
            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }
}
