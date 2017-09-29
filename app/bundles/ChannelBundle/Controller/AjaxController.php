<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

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
        $queueModel     = $this->getModel('channel.queue');
        $queuedMessage  = $queueModel->getEntity($messageQueueId);
        $this->factory->getLogger()->addError(print_r($messageQueueId, true));
        if ($queuedMessage) {
            $this->factory->getLogger()->addError(print_r($messageQueueId, true));
            $queuedMessage->setStatus('cancelled');
            $queueModel->saveEntity($queuedMessage);
            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }
}
