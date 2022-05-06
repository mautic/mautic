<?php

namespace Mautic\ChannelBundle\Model;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Event\MessageQueueBatchProcessEvent;
use Mautic\ChannelBundle\Event\MessageQueueEvent;
use Mautic\ChannelBundle\Event\MessageQueueProcessEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\Event;

class MessageQueueModel extends FormModel
{
    /** @var string A default message reschedule interval */
    const DEFAULT_RESCHEDULE_INTERVAL = 'PT15M';

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function __construct(LeadModel $leadModel, CompanyModel $companyModel, CoreParametersHelper $coreParametersHelper)
    {
        $this->leadModel            = $leadModel;
        $this->companyModel         = $companyModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\Mautic\ChannelBundle\Entity\MessageQueueRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticChannelBundle:MessageQueue');
    }

    /**
     * @param        $channel
     * @param        $channelId
     * @param null   $campaignEventId
     * @param int    $attempts
     * @param int    $priority
     * @param null   $messageQueue
     * @param string $statTableName
     * @param string $statContactColumn
     * @param string $statSentColumn
     *
     * @return array
     */
    public function processFrequencyRules(
        array &$leads,
        $channel,
        $channelId,
        $campaignEventId = null,
        $attempts = 3,
        $priority = MessageQueue::PRIORITY_NORMAL,
        $messageQueue = null,
        $statTableName = 'email_stats',
        $statContactColumn = 'lead_id',
        $statSentColumn = 'date_sent'
    ) {
        $leadIds = array_keys($leads);
        $leadIds = array_combine($leadIds, $leadIds);

        /** @var \Mautic\LeadBundle\Entity\FrequencyRuleRepository $frequencyRulesRepo */
        $frequencyRulesRepo     = $this->em->getRepository('MauticLeadBundle:FrequencyRule');
        $defaultFrequencyNumber = $this->coreParametersHelper->get($channel.'_frequency_number');
        $defaultFrequencyTime   = $this->coreParametersHelper->get($channel.'_frequency_time');

        $dontSendTo = $frequencyRulesRepo->getAppliedFrequencyRules(
            $channel,
            $leadIds,
            $defaultFrequencyNumber,
            $defaultFrequencyTime,
            $statTableName,
            $statContactColumn,
            $statSentColumn
        );

        $queuedContacts = [];
        if (!empty($dontSendTo)) {
            foreach ($dontSendTo as $frequencyRuleMet) {
                // We only deal with date intervals here (no time intervals) so it's safe to use 'P'
                $scheduleInterval = new \DateInterval('P1'.substr($frequencyRuleMet['frequency_time'], 0, 1));
                if ($messageQueue && isset($messageQueue[$frequencyRuleMet['lead_id']])) {
                    $this->reschedule($messageQueue[$frequencyRuleMet['lead_id']], $scheduleInterval);
                } else {
                    // Queue this message to be processed by frequency and priority
                    $this->queue(
                        [$leads[$frequencyRuleMet['lead_id']]],
                        $channel,
                        $channelId,
                        $scheduleInterval,
                        $attempts,
                        $priority,
                        $campaignEventId
                    );
                }
                $queuedContacts[$frequencyRuleMet['lead_id']] = $frequencyRuleMet['lead_id'];
                unset($leads[$frequencyRuleMet['lead_id']]);
            }
        }

        return $queuedContacts;
    }

    /**
     * Adds messages to the queue.
     *
     * @param array    $leads
     * @param string   $channel
     * @param int      $channelId
     * @param int      $maxAttempts
     * @param int      $priority
     * @param int|null $campaignEventId
     * @param array    $options
     *
     * @return bool
     */
    public function queue(
        $leads,
        $channel,
        $channelId,
        \DateInterval $scheduledInterval,
        $maxAttempts = 1,
        $priority = 1,
        $campaignEventId = null,
        $options = []
    ) {
        $messageQueues = [];

        $scheduledDate = (new \DateTime())->add($scheduledInterval);

        foreach ($leads as $lead) {
            $leadId = (is_array($lead)) ? $lead['id'] : $lead->getId();
            if (!empty($this->getRepository()->findMessage($channel, $channelId, $leadId))) {
                continue;
            }

            $messageQueue = new MessageQueue();
            if ($campaignEventId) {
                $messageQueue->setEvent($this->em->getReference('MauticCampaignBundle:Event', $campaignEventId));
            }
            $messageQueue->setChannel($channel);
            $messageQueue->setChannelId($channelId);
            $messageQueue->setDatePublished(new \DateTime());
            $messageQueue->setMaxAttempts($maxAttempts);
            $messageQueue->setLead(
                ($lead instanceof Lead) ? $lead : $this->em->getReference('MauticLeadBundle:Lead', $leadId)
            );
            $messageQueue->setPriority($priority);
            $messageQueue->setScheduledDate($scheduledDate);
            $messageQueue->setOptions($options);

            $messageQueues[] = $messageQueue;
        }

        if ($messageQueues) {
            $this->saveEntities($messageQueues);
            $this->em->clear(MessageQueue::class);
        }

        return true;
    }

    /**
     * @deprecated to be removed in 3.0; use queue method instead
     *
     * @param       $leads
     * @param       $channel
     * @param       $channelId
     * @param null  $scheduledInterval
     * @param int   $maxAttempts
     * @param int   $priority
     * @param null  $campaignEventId
     * @param array $options
     *
     * @return bool
     */
    public function addToQueue(
        $leads,
        $channel,
        $channelId,
        $scheduledInterval = null,
        $maxAttempts = 1,
        $priority = 1,
        $campaignEventId = null,
        $options = []
    ) {
        $messageQueues = [];

        if (!$scheduledInterval) {
            $scheduledDate = new \DateTime();
        } elseif (!$scheduledInterval instanceof \DateTime) {
            $scheduledInterval = (('H' === $scheduledInterval) ? 'PT' : 'P').$scheduledInterval;
            $scheduledDate     = (new \DateTime())->add(new \DateInterval($scheduledInterval));
        }

        foreach ($leads as $lead) {
            $leadId = (is_array($lead)) ? $lead['id'] : $lead->getId();
            if (!empty($this->getRepository()->findMessage($channel, $channelId, $leadId))) {
                continue;
            }

            $messageQueue = new MessageQueue();
            if ($campaignEventId) {
                $messageQueue->setEvent($this->em->getReference('MauticCampaignBundle:Event', $campaignEventId));
            }
            $messageQueue->setChannel($channel);
            $messageQueue->setChannelId($channelId);
            $messageQueue->setDatePublished(new \DateTime());
            $messageQueue->setMaxAttempts($maxAttempts);
            $messageQueue->setLead(
                ($lead instanceof Lead) ? $lead : $this->em->getReference('MauticLeadBundle:Lead', $leadId)
            );
            $messageQueue->setPriority($priority);
            $messageQueue->setScheduledDate($scheduledDate);
            $messageQueue->setOptions($options);

            $messageQueues[] = $messageQueue;
        }

        if ($messageQueues) {
            $this->saveEntities($messageQueues);
            $this->em->clear(MessageQueue::class);
        }

        return true;
    }

    /**
     * @param null $channel
     * @param null $channelId
     *
     * @return int
     */
    public function sendMessages($channel = null, $channelId = null)
    {
        // Note when the process started for batch purposes
        $processStarted = new \DateTime();
        $limit          = 50;
        $counter        = 0;
        while ($queue = $this->getRepository()->getQueuedMessages($limit, $processStarted, $channel, $channelId)) {
            $counter += $this->processMessageQueue($queue);

            // Remove the entities from memory
            $this->em->clear(MessageQueue::class);
            $this->em->clear(Lead::class);
            $this->em->clear(\Mautic\CampaignBundle\Entity\Event::class);
        }

        return $counter;
    }

    /**
     * @param $queue
     *
     * @return int
     */
    public function processMessageQueue($queue)
    {
        if (!is_array($queue)) {
            if (!$queue instanceof MessageQueue) {
                throw new \InvalidArgumentException('$queue must be an instance of '.MessageQueue::class);
            }

            $queue = [$queue->getId() => $queue];
        }

        $counter   = 0;
        $contacts  = [];
        $byChannel = [];

        // Lead entities will not have profile fields populated due to the custom field use - therefore to optimize resources,
        // get a list of leads to fetch details all at once along with company details for dynamic email content, etc
        /** @var MessageQueue $message */
        foreach ($queue as $message) {
            if ($message->getLead()) {
                $contacts[$message->getId()] = $message->getLead()->getId();
            }
        }
        if (!empty($contacts)) {
            $contactData = $this->leadModel->getRepository()->getContacts($contacts);
            $companyData = $this->companyModel->getRepository()->getCompaniesForContacts($contacts);
            foreach ($contacts as $messageId => $contactId) {
                $contactData[$contactId]['companies'] = isset($companyData[$contactId]) ? $companyData[$contactId] : null;
                $queue[$messageId]->getLead()->setFields($contactData[$contactId]);
            }
        }
        // Group queue by channel and channel ID - this make it possible for processing listeners to batch process such as
        // sending emails in batches to 3rd party transactional services via HTTP APIs
        foreach ($queue as $key => $message) {
            if (MessageQueue::STATUS_SENT == $message->getStatus()) {
                unset($queue[$key]);
                continue;
            }

            $messageChannel   = $message->getChannel();
            $messageChannelId = $message->getChannelId();
            if (!$messageChannelId) {
                $messageChannelId = 0;
            }

            if (!isset($byChannel[$messageChannel])) {
                $byChannel[$messageChannel] = [];
            }
            if (!isset($byChannel[$messageChannel][$messageChannelId])) {
                $byChannel[$messageChannel][$messageChannelId] = [];
            }

            $byChannel[$messageChannel][$messageChannelId][] = $message;
        }

        // First try to batch process each channel
        foreach ($byChannel as $messageChannel => $channelMessages) {
            foreach ($channelMessages as $messageChannelId => $messages) {
                $event  = new MessageQueueBatchProcessEvent($messages, $messageChannel, $messageChannelId);
                $ignore = null;
                $this->dispatchEvent('process_batch_message_queue', $ignore, false, $event);
            }
        }
        unset($byChannel);

        // Now check to see if the message was processed by the listener and if not
        // send it through a single process event listener
        foreach ($queue as $message) {
            if (!$message->isProcessed()) {
                $event = new MessageQueueProcessEvent($message);
                $this->dispatchEvent('process_message_queue', $message, false, $event);
            }

            if ($message->isSuccess()) {
                ++$counter;
                $message->setSuccess();
                $message->setLastAttempt(new \DateTime());
                $message->setDateSent(new \DateTime());
                $message->setStatus(MessageQueue::STATUS_SENT);
            } elseif ($message->isFailed()) {
                // Failure such as email delivery issue or something so retry in a short time
                $this->reschedule($message, new \DateInterval(self::DEFAULT_RESCHEDULE_INTERVAL));
            } // otherwise assume the listener did something such as rescheduling the message
        }

        //add listener
        $this->saveEntities($queue);

        return $counter;
    }

    /**
     * @param bool $persist
     */
    public function reschedule($message, \DateInterval $rescheduleInterval, $leadId = null, $channel = null, $channelId = null, $persist = false)
    {
        if (!$message instanceof MessageQueue && $leadId && $channel && $channelId) {
            $message = $this->getRepository()->findMessage($channel, $channelId, $leadId);
            $persist = true;
        }

        if (!$message) {
            return;
        }

        $message->setAttempts($message->getAttempts() + 1);
        $message->setLastAttempt(new \DateTime());
        $rescheduleTo = clone $message->getScheduledDate();

        $rescheduleTo->add($rescheduleInterval);
        $message->setScheduledDate($rescheduleTo);
        $message->setStatus(MessageQueue::STATUS_RESCHEDULED);

        if ($persist) {
            $this->saveEntity($message);
        }

        // Mark as processed for listeners
        $message->setProcessed();
    }

    /**
     * @deprecated to be removed in 3.0; use reschedule method instead
     *
     * @param        $message
     * @param string $rescheduleInterval
     * @param null   $leadId
     * @param null   $channel
     * @param null   $channelId
     * @param bool   $persist
     */
    public function rescheduleMessage($message, $rescheduleInterval = null, $leadId = null, $channel = null, $channelId = null, $persist = false)
    {
        $rescheduleInterval = null == $rescheduleInterval ? self::DEFAULT_RESCHEDULE_INTERVAL : ('P'.$rescheduleInterval);

        return $this->reschedule($message, new \DateInterval($rescheduleInterval), $leadId, $channel, $channelId, $persist);
    }

    /**
     * @param       $channel
     * @param array $channelIds
     */
    public function getQueuedChannelCount($channel, $channelIds = [])
    {
        return $this->getRepository()->getQueuedChannelCount($channel, $channelIds);
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
        switch ($action) {
            case 'process_message_queue':
                $name = ChannelEvents::PROCESS_MESSAGE_QUEUE;
                break;
            case 'process_batch_message_queue':
                $name = ChannelEvents::PROCESS_MESSAGE_QUEUE_BATCH;
                break;
            case 'post_save':
                $name = ChannelEvents::MESSAGE_QUEUED;
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
