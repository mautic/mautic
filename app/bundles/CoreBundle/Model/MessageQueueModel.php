<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Doctrine\DBAL\DBALException;

use Mautic\CoreBundle\Event\MessageQueueProcessEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Event\MessageQueueEvent;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Entity\MessageQueueRepository;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;


/**
 * Class MessageQueueModel
 */
class MessageQueueModel extends FormModel
{

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * messageModel constructor.
     *
     *
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, LeadModel $leadModel) {

        $this->leadModel = $leadModel;
    }

    public function getRepository()
    {
        return $this->em->getRepository('MauticCoreBundle:MessageQueue');
    }

    /**
     * @param $leads
     * @param $campaignId
     * @param $channel
     * @param $channelId
     * @param $options
     * @param int $maxAttempts
     * @param int $priority
     * @return bool|MessageQueue
     */
    public function addToQueue($leads, $campaignId, $channel, $channelId, $maxAttempts = 1, $priority = 1)
    {
        $messageQueues = [];

        echo $channel;
        echo $channelId;

        foreach ($leads as $lead){

            if (empty($this->getRepository()->findMessage($channel,$channelId,$lead['id']))) {
                $leadEntity = $this->leadModel->getEntity($lead['id']);
                $messageQueue = new MessageQueue();
                $messageQueue->setCampaign($campaignId);
                $messageQueue->setChannel($channel);
                $messageQueue->setChannelId($channelId);
                $messageQueue->setDatePublished(new \DateTime());
                $messageQueue->setMaxAttempts($maxAttempts);
                $messageQueue->setLead($leadEntity);
                $messageQueue->setPriority($priority);
                $messageQueue->setScheduledDate(new \DateTime());

                $messageQueues[] = $messageQueue;
            }
        }

        $this->saveEntities($messageQueues);

        return true;
    }

    /**
     * @param null $channel
     * @param null $channelId
     */
    public function sendMessages($channel = null, $channelId = null)
    {
        $queue = $this->getRepository()->getQueuedMessages($channel, $channelId);
        /* @var $queueItem messageQueue */
        $messages = [];

        foreach ($queue as $queueItem)
        {
            $success = false;
            $message = $this->getRepository()->getEntity((int)$queueItem['id']);
            $event = new MessageQueueProcessEvent($message);
            $new = false;
            $success = $this->dispatchEvent('process_message_queue',$message, $new, $event);
            $lead = $message->getLead();

            if ($success) {
                $message->setAttempts($message->getAttempts() + 1);
                $message->setSuccess(true);
                $message->setLastAttempt(new \DateTime());
                $message->setDateSent(new \DateTime());
                $message->setStatus('sent');
                $messages[$queueItem['channelId']] = $message;
            } else {
                $this->rescheduleMessage($lead->getId(),$queueItem['channel'],  $queueItem['channelId'],  '15M', $message);
            }
        }

        //add listener
        $this->saveEntities($messages);
    }

    /**
     * @param $queue
     * @param $leadId
     * @param string $rescheduleDate
     */
    public function rescheduleMessage($leadId, $channel, $channelId, $rescheduleDate = '15M', $queue)
    {
        if (!$queue) {
            $queue = $this->getRepository()->findMessage($channel,$channelId,$leadId);
        }
        if ($queue) {
            $queue->setAttempts($queue->getAttempts() + 1);
            $queue->setLastAttempt(new \DateTime());
            $queue->setScheduledDate($queue->getScheduledDate()->add(new DateInterval('PT'.$rescheduleDate)));
            $queue->setStatus('rescheduled');
            $this->saveEntity($queue);
        }

    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $entity
     * @param $isNew
     * @param $event
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof MessageQueue) {
            throw new MethodNotAllowedHttpException(['Message Queue']);
        }
        switch ($action) {
            case "process_message_queue":
                $name = CoreEvents::PROCESS_MESSAGE_QUEUE;
                break;
            case "post_save":
                $name = CoreEvents::MESSAGE_QUEUED;
                break;
            default:
                return null;
        }
        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new MessageQueueEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return null;
        }
    }

}