<?php

namespace Mautic\ChannelBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Event\MessageQueueBatchProcessEvent;
use Mautic\ChannelBundle\Event\MessageQueueEvent;
use Mautic\ChannelBundle\Event\MessageQueueProcessEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<MessageQueue>
 */
class MessageQueueModel extends FormModel
{
    /**
     * @var string A default message reschedule interval
     */
    public const DEFAULT_RESCHEDULE_INTERVAL = 'PT15M';

    public function __construct(
        protected LeadModel $leadModel,
        protected CompanyModel $companyModel,
        CoreParametersHelper $coreParametersHelper,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return \Mautic\ChannelBundle\Entity\MessageQueueRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(\Mautic\ChannelBundle\Entity\MessageQueue::class);
    }

    /**
     * @param int    $attempts
     * @param int    $priority
     * @param mixed  $messageQueue
     * @param string $statTableName
     * @param string $statContactColumn
     * @param string $statSentColumn
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
    ): array {
        $leadIds = array_keys($leads);
        $leadIds = array_combine($leadIds, $leadIds);

        /** @var \Mautic\LeadBundle\Entity\FrequencyRuleRepository $frequencyRulesRepo */
        $frequencyRulesRepo     = $this->em->getRepository(\Mautic\LeadBundle\Entity\FrequencyRule::class);
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
    ): bool {
        $messageQueues = [];

        $scheduledDate = (new \DateTime())->add($scheduledInterval);

        foreach ($leads as $lead) {
            $leadId = (is_array($lead)) ? $lead['id'] : $lead->getId();
            if (!empty($this->getRepository()->findMessage($channel, $channelId, $leadId))) {
                continue;
            }

            $messageQueue = new MessageQueue();
            if ($campaignEventId) {
                $messageQueue->setEvent($this->em->getReference(\Mautic\CampaignBundle\Entity\Event::class, $campaignEventId));
            }
            $messageQueue->setChannel($channel);
            $messageQueue->setChannelId($channelId);
            $messageQueue->setDatePublished(new \DateTime());
            $messageQueue->setMaxAttempts($maxAttempts);
            $messageQueue->setLead(
                ($lead instanceof Lead) ? $lead : $this->em->getReference(\Mautic\LeadBundle\Entity\Lead::class, $leadId)
            );
            $messageQueue->setPriority($priority);
            $messageQueue->setScheduledDate($scheduledDate);
            $messageQueue->setOptions($options);

            $messageQueues[] = $messageQueue;
        }

        if ($messageQueues) {
            $this->saveEntities($messageQueues);
            $messageQueueRepository = $this->getRepository();
            $messageQueueRepository->detachEntities($messageQueues);
        }

        return true;
    }

    public function sendMessages($channel = null, $channelId = null): int
    {
        // Note when the process started for batch purposes
        $processStarted = new \DateTime();
        $limit          = 50;
        $counter        = 0;

        foreach ($this->getRepository()->getQueuedMessages($limit, $processStarted, $channel, $channelId) as $queue) {
            $counter += $this->processMessageQueue($queue);
            $event   = $queue->getEvent();
            $lead    = $queue->getLead();

            if ($event) {
                $this->em->detach($event);
            }
            $this->em->detach($lead);
            $this->em->detach($queue);
        }

        return $counter;
    }

    public function processMessageQueue($queue): int
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
            foreach ($contacts as $messageId => $contactId) {
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

        // add listener
        $this->saveEntities($queue);

        return $counter;
    }

    /**
     * @param bool $persist
     */
    public function reschedule($message, \DateInterval $rescheduleInterval, $leadId = null, $channel = null, $channelId = null, $persist = false): void
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
     * @param string $rescheduleInterval
     * @param bool   $persist
     */
    public function rescheduleMessage($message, $rescheduleInterval = null, $leadId = null, $channel = null, $channelId = null, $persist = false): void
    {
        $rescheduleInterval = null == $rescheduleInterval ? self::DEFAULT_RESCHEDULE_INTERVAL : ('P'.$rescheduleInterval);

        $this->reschedule($message, new \DateInterval($rescheduleInterval), $leadId, $channel, $channelId, $persist);
    }

    /**
     * @param array $channelIds
     */
    public function getQueuedChannelCount($channel, $channelIds = []): int
    {
        return $this->getRepository()->getQueuedChannelCount($channel, $channelIds);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
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
            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }
}
