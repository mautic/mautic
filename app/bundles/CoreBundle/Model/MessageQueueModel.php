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
use Doctrine\ORM\EntityNotFoundException;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Entity\MessageQueue;


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
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var CampaignModel
     */
    protected $emailModel;

    /**
     * messageModel constructor.
     *
     *
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, EmailModel $emailModel, CampaignModel $campaignModel, LeadModel $leadModel) {
        $this->emailModel = $emailModel;
        $this->campaignModel = $campaignModel;
        $this->leadModel = $leadModel;
    }

    public function getRepository()
    {
        return $this->em->getRepository('MauticCoreBundle:MessageQueue');
    }

    /**
     * @param $channel
     * @param $channelId
     * @param CampaignEvent $event
     * @param $messageQueue
     * @param int $maxAttempts
     * @param int $priority
     */
    public function addToQueue(Lead $lead, $campaignId, $channel, $channelId,  $options,  $maxAttempts = 1, $priority = 1)
    {

        if (!empty($this->getRepository()->findMessage($channel,$channelId,$lead->getId()))) {
            $messageQueue = new MessageQueue();
            $messageQueue->setCampaign($campaignId);
            $messageQueue->setChannel($channel);
            $messageQueue->setChannelId($channelId);
            $messageQueue->setDatePublished(new \DateTime());
            $messageQueue->setMaxAttempts($maxAttempts);
            $messageQueue->setLead($lead);
            $messageQueue->setPriority($priority);
            $messageQueue->setScheduledDate(new \DateTime());
            $messageQueue->setOptions($options);

            $this->getRepository()->saveEntity($messageQueue);

            return true;
        }
        return false;
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
            $lead = $this->leadModel->getEntity($queueItem['lead']);
            $leadCredentials = ($lead instanceof Lead) ? $lead->getProfileFields() : $lead;
            $leadCredentials['owner_id'] = (
                ($lead instanceof Lead) && ($owner = $lead->getOwner())
            ) ? $owner->getId() : 0;

            $options = $queueItem['options'];

            if ($queueItem['channel'] == 'email'){
                $message = $this->emailModel->getEntity($queueItem['channelId']);
                $success = $this->emailModel->sendEmail($message, $leadCredentials, $options);

            }
            $message = $this->getRepository()->getEntity((int)$queueItem['id']);
            if ($success) {
                $message->setAttempts($message->getAttempts() + 1);
                $message->setSuccess(true);
                $message->setLastAttempt(new \DateTime());
                $message->setDateSent(new \DateTime());
                $queue->setStatus('sent');
                $messages[$queueItem['channelId']] = $message;
            } else {
                $this->rescheduleMessage($message, $lead);
            }
        }

        //add listener
        $this->getRepository()->saveEntities($messages);
    }

    /**
     *
     */
    public function rescheduleMessage($queue, $lead) {

        if (is_object($queue)) {
            $frequencyRule = $this->leadModel->getFrequencyRule($lead);
            $queue->setAttempts($queue->getAttempts() + 1);
            $queue->setLastAttempt(new \DateTime());
            $queue->setScheduledDate($queue->getScheduledDate()->add('PT'.$frequencyRule['frequency_number'].substr($frequencyRule['frequency_time'],0,1)));
            $queue->setStatus('rescheduled');
            $this->getRepository()->saveEntity($queue);
        }

    }
}