<?php

namespace Mautic\ChannelBundle\Controller;

use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    private MessageQueueModel $messageQueueModel;

    #[Required]
    public function autowireChannelAjaxController(
        MessageQueueModel $messageQueueModel,
    ): void {
        $this->messageQueueModel = $messageQueueModel;
    }

    public function cancelQueuedMessageEventAction(Request $request): JsonResponse
    {
        $dataArray      = ['success' => 0];
        $messageQueueId = (int) $request->request->get('channelId');
        $queuedMessage  = $this->messageQueueModel->getEntity($messageQueueId);
        if ($queuedMessage) {
            $queuedMessage->setStatus('cancelled');
            $this->messageQueueModel->saveEntity($queuedMessage);
            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getMarketingMessageSendToDncStatusAction(Request $request): JsonResponse
    {
        $dataArray = [];

        /** @var MessageModel $model */
        $model    = $this->getModel('channel.message');
        $objectId = $request->get('id');

        if ($objectId && $entity = $model->getEntity($objectId)) {
            /** @var EmailModel $model */
            $emailModel = $this->getModel('email');

            foreach ($entity->getChannels() as $channel) {
                if ('email' !== $channel->getChannel() || empty($channel->getChannelId())) {
                    continue;
                }

                if ($email = $emailModel->getEntity($channel->getChannelId())) {
                    $yesOrNo = $email->getSendToDnc() ? 'yes' : 'no';

                    $dataArray['sendToDncText']      = $this->translator->trans("mautic.core.form.{$yesOrNo}");
                    $dataArray['sendToDncStatus']    = $email->getSendToDnc();
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
