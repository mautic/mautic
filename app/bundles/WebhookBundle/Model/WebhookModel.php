<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Model;

use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\EventRepository;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\LogRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Event as Events;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\Form\Type\WebhookType;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class WebhookModel extends FormModel
{
    /**
     *  2 possible types of the processing of the webhooks.
     */
    const COMMAND_PROCESS   = 'command_process';
    const IMMEDIATE_PROCESS = 'immediate_process';

    private const DELETE_BATCH_LIMIT = 5000;

    /**
     * Whet queue mode is turned on.
     *
     * @var string
     */
    protected $queueMode;

    /**
     * How many entities to add into one queued webhook.
     *
     * @var int
     */
    protected $webhookLimit;

    /**
     * How long the webhook processing can run in seconds.
     *
     * @var int
     */
    private $webhookTimeLimit;

    /**
     * How many responses in 1 row can fail until the webhook disables itself.
     *
     * @var int
     */
    protected $disableLimit;

    /**
     * How many seconds will we wait for the response.
     *
     * @var int in seconds
     */
    protected $webhookTimeout;

    /**
     * The key is queue ID, the value is the WebhookQueue object.
     *
     * @var array
     */
    protected $webhookQueueIdList = [];

    /**
     * How many recent log records should be kept.
     *
     * @var int
     */
    protected $logMax;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Queued events default order by dir
     * Possible values: ['ASC', 'DESC'].
     *
     * @var string
     */
    protected $eventsOrderByDir;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Timestamp when the webhook processing starts.
     *
     * @var float
     */
    private $startTime;

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        SerializerInterface $serializer,
        Client $httpClient,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->setConfigProps($coreParametersHelper);
        $this->serializer        = $serializer;
        $this->httpClient        = $httpClient;
        $this->eventDispatcher   = $eventDispatcher;
    }

    /**
     * @param Webhook $entity
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (null === $entity->getSecret()) {
            $entity->setSecret(EncryptionHelper::generateKey());
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param Webhook $entity
     * @param         $formFactory
     * @param null    $action
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $params = [])
    {
        if (!$entity instanceof Webhook) {
            throw new MethodNotAllowedHttpException(['Webhook']);
        }

        if (!empty($action)) {
            $params['action'] = $action;
        }

        $params['events'] = $this->getEvents();

        return $formFactory->create(WebhookType::class, $entity, $params);
    }

    /**
     * @return Webhook|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Webhook();
        }

        return parent::getEntity($id);
    }

    /**
     * @return WebhookRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Webhook::class);
    }

    /**
     * Gets array of custom events from bundles subscribed MauticWehbhookBundle::WEBHOOK_ON_BUILD.
     *
     * @return mixed
     */
    public function getEvents()
    {
        static $events;

        if (empty($events)) {
            //build them
            $events = [];
            $event  = new Events\WebhookBuilderEvent($this->translator);
            $this->dispatcher->dispatch(WebhookEvents::WEBHOOK_ON_BUILD, $event);
            $events = $event->getEvents();
        }

        return $events;
    }

    /**
     * Get a list of webhooks by matching events.
     *
     * @param string $type string of event type
     *
     * @return array
     */
    public function getEventWebooksByType($type)
    {
        return $this->getEventRepository()->getEntitiesByEventType($type);
    }

    /**
     * @param $type
     * @param $payload
     * @param $groups
     */
    public function queueWebhooksByType($type, $payload, array $groups = [])
    {
        return $this->queueWebhooks(
            $this->getEventWebooksByType($type),
            $payload,
            $groups
        );
    }

    /**
     * @param $webhookEvents
     * @param $payload
     */
    public function queueWebhooks($webhookEvents, $payload, array $serializationGroups = [])
    {
        if (!count($webhookEvents) || !is_array($webhookEvents)) {
            return;
        }

        /** @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($webhookEvents as $event) {
            $webhook = $event->getWebhook();
            $queue   = $this->queueWebhook($webhook, $event, $payload, $serializationGroups);

            if (self::COMMAND_PROCESS === $this->queueMode) {
                // Queue to the database to process later
                $this->getQueueRepository()->saveEntity($queue);
            } else {
                // Immediately process
                $this->processWebhook($webhook, $queue);
            }
        }
    }

    /**
     * Creates a WebhookQueue entity, sets the date and returns the created entity.
     *
     * @param $event
     * @param $payload
     *
     * @return WebhookQueue
     */
    public function queueWebhook(Webhook $webhook, $event, $payload, array $serializationGroups = [])
    {
        $serializedPayload = $this->serializeData($payload, $serializationGroups);

        $queue = new WebhookQueue();
        $queue->setWebhook($webhook);
        $queue->setDateAdded(new \DateTime());
        $queue->setEvent($event);
        $queue->setPayload($serializedPayload);

        // fire events for when the queues are created
        if ($this->dispatcher->hasListeners(WebhookEvents::WEBHOOK_QUEUE_ON_ADD)) {
            $webhookQueueEvent = $event = new Events\WebhookQueueEvent($queue, $webhook, true);
            $this->dispatcher->dispatch(WebhookEvents::WEBHOOK_QUEUE_ON_ADD, $webhookQueueEvent);
        }

        return $queue;
    }

    /**
     * Execute a list of webhooks to their specified endpoints.
     *
     * @param array|\Doctrine\ORM\Tools\Pagination\Paginator $webhooks
     */
    public function processWebhooks($webhooks)
    {
        $this->startTime = microtime(true);

        foreach ($webhooks as $webhook) {
            $this->processWebhook($webhook);
        }
    }

    /**
     * @param WebhookQueue $queue
     *
     * @return bool
     */
    public function processWebhook(Webhook $webhook, WebhookQueue $queue = null)
    {
        // get the webhook payload
        $payload = $this->getWebhookPayload($webhook, $queue);

        // if there wasn't a payload we can stop here
        if (empty($payload)) {
            return false;
        }

        $start            = microtime(true);
        $webhookQueueRepo = $this->getQueueRepository();

        try {
            $response = $this->httpClient->post($webhook->getWebhookUrl(), $payload, $webhook->getSecret());

            // remove successfully processed queues from the Webhook object so they won't get stored again
            $queueIds        = array_keys($this->webhookQueueIdList);
            $chunkedQueueIds = array_chunk($queueIds, self::DELETE_BATCH_LIMIT);

            foreach ($chunkedQueueIds as $queueIds) {
                $webhookQueueRepo->deleteQueuesById($queueIds);
            }

            $responseBody = $response->getBody()->getContents();
            if (!$responseBody) {
                $responseBody = null; // Save null value to database
            }

            $responseStatusCode = $response->getStatusCode();

            $this->addLog($webhook, $response->getStatusCode(), (microtime(true) - $start), $responseBody);

            // throw an error exception if we don't get a 200 back
            if ($responseStatusCode >= 300 || $responseStatusCode < 200) {
                // The receiver of the webhook is telling us to stop bothering him with our requests by code 410
                if (410 === $responseStatusCode) {
                    $this->killWebhook($webhook, 'mautic.webhook.stopped.reason.410');
                }

                throw new \ErrorException($webhook->getWebhookUrl().' returned '.$responseStatusCode);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if ($this->isSick($webhook)) {
                $this->killWebhook($webhook);
                $message .= ' '.$this->translator->trans('mautic.webhook.killed', ['%limit%' => $this->disableLimit]);
            }

            // log any errors but allow the script to keep running
            $this->logger->addError($message);

            // log that the request failed to display it to the user
            $this->addLog($webhook, 'N/A', (microtime(true) - $start), $message);

            return false;
        }

        // Run this on command as well as immediate send because if switched from queue to immediate
        // it can have some rows in the queue which will be send in every webhook forever
        if (!empty($this->webhookQueueIdList)) {
            // delete all the queued items we just processed
            $webhookQueueRepo->deleteQueuesById(array_keys($this->webhookQueueIdList));
            $queueCount = $webhookQueueRepo->getQueueCountByWebhookId($webhook->getId());

            // reset the array to blank so none of the IDs are repeated
            $this->webhookQueueIdList = [];

            // if there are still items in the queue after processing we re-process
            // WARNING: this is recursive
            if ($queueCount > 0 && !$this->isProcessingExpired()) {
                $this->processWebhook($webhook);
            }
        }

        return true;
    }

    /**
     * Look into the history and check if all the responses we care about had failed.
     * But let it run for a while after the user modified it. Lets not aggravate the user.
     *
     * @return bool
     */
    public function isSick(Webhook $webhook)
    {
        // Do not mess with the user will! (at least not now)
        if ($webhook->wasModifiedRecently()) {
            return false;
        }

        $successRadio = $this->getLogRepository()->getSuccessVsErrorStatusCodeRatio($webhook->getId(), $this->disableLimit);

        // If there are no log rows yet, consider it healthy
        if (null === $successRadio) {
            return false;
        }

        return !$successRadio;
    }

    /**
     * Unpublish the webhook so it will stop emit the requests
     * and notify user about it.
     *
     * @param string $reason
     */
    public function killWebhook(Webhook $webhook, $reason = 'mautic.webhook.stopped.reason')
    {
        $webhook->setIsPublished(false);
        $this->saveEntity($webhook);

        $event = new WebhookEvent($webhook, false, $reason);
        $this->eventDispatcher->dispatch(WebhookEvents::WEBHOOK_KILL, $event);
    }

    /**
     * Add a log for the webhook response HTTP status and save it.
     *
     * @param int    $statusCode
     * @param float  $runtime    in seconds
     * @param string $note
     */
    public function addLog(Webhook $webhook, $statusCode, $runtime, $note = null)
    {
        $log = new Log();

        if ($webhook->getId()) {
            $log->setWebhook($webhook);
            $this->getLogRepository()->removeOldLogs($webhook->getId(), $this->logMax);
        }

        $log->setNote($note);
        $log->setRuntime($runtime);
        $log->setStatusCode($statusCode);
        $log->setDateAdded(new \DateTime());
        $webhook->addLog($log);

        if ($webhook->getId()) {
            $this->saveEntity($webhook);
        }
    }

    /**
     * @return WebhookQueueRepository
     */
    public function getQueueRepository()
    {
        return $this->em->getRepository(WebhookQueue::class);
    }

    /**
     * @return EventRepository
     */
    public function getEventRepository()
    {
        return $this->em->getRepository(Event::class);
    }

    /**
     * @return LogRepository
     */
    public function getLogRepository()
    {
        return $this->em->getRepository(Log::class);
    }

    /**
     * Get the payload from the webhook.
     *
     * @param WebhookQueue $queue
     *
     * @return array
     */
    public function getWebhookPayload(Webhook $webhook, WebhookQueue $queue = null)
    {
        if ($payload = $webhook->getPayload()) {
            return $payload;
        }

        $payload = [];

        if (self::COMMAND_PROCESS === $this->queueMode) {
            $queuesArray = $this->getWebhookQueues($webhook);
        } else {
            $queuesArray = [isset($queue) ? [$queue] : []];
        }

        /* @var WebhookQueue $queue */
        foreach ($queuesArray as $queues) {
            foreach ($queues as $queue) {
                /** @var \Mautic\WebhookBundle\Entity\Event $event */
                $event = $queue->getEvent();
                $type  = $event->getEventType();

                // create new array level for each unique event type
                if (!isset($payload[$type])) {
                    $payload[$type] = [];
                }

                $queuePayload              = json_decode($queue->getPayload(), true);
                $queuePayload['timestamp'] = $queue->getDateAdded()->format('c');

                // its important to decode the payload form the DB as we re-encode it with the
                $payload[$type][] = $queuePayload;

                // Add to the webhookQueueIdList only if ID exists.
                // That means if it was stored to DB and not sent via immediate send.
                if ($queue->getId()) {
                    $this->webhookQueueIdList[$queue->getId()] = $queue;

                    // Clear the WebhookQueue entity from memory
                    $this->em->detach($queue);
                }
            }
        }

        return $payload;
    }

    /**
     * Get the queues and order by date so we get events.
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getWebhookQueues(Webhook $webhook)
    {
        /** @var WebhookQueueRepository $queueRepo */
        $queueRepo = $this->getQueueRepository();

        return $queueRepo->getEntities(
            [
                'iterator_mode' => true,
                'start'         => 0,
                'limit'         => $this->webhookLimit,
                'orderBy'       => $queueRepo->getTableAlias().'.dateAdded',
                'orderByDir'    => $this->getEventsOrderbyDir($webhook),
                'filter'        => [
                    'force' => [
                        [
                            'column' => 'IDENTITY('.$queueRepo->getTableAlias().'.webhook)',
                            'expr'   => 'eq',
                            'value'  => $webhook->getId(),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Returns either Webhook's orderbyDir or the value from configuration as default.
     *
     * @return string
     */
    public function getEventsOrderbyDir(Webhook $webhook = null)
    {
        // Try to get the value from Webhook
        if ($webhook && $orderByDir = $webhook->getEventsOrderbyDir()) {
            return $orderByDir;
        }

        // Use the global config value if it's not set in the Webhook
        return $this->eventsOrderByDir;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, SymfonyEvent $event = null)
    {
        if (!$entity instanceof Webhook) {
            throw new MethodNotAllowedHttpException(['Webhook'], 'Entity must be of class Webhook()');
        }

        switch ($action) {
            case 'pre_save':
                $name = WebhookEvents::WEBHOOK_PRE_SAVE;
                break;
            case 'post_save':
                $name = WebhookEvents::WEBHOOK_POST_SAVE;
                break;
            case 'pre_delete':
                $name = WebhookEvents::WEBHOOK_PRE_DELETE;
                break;
            case 'post_delete':
                $name = WebhookEvents::WEBHOOK_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new WebhookEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param       $payload
     * @param array $groups
     *
     * @return mixed|string
     */
    public function serializeData($payload, $groups = [], array $customExclusionStrategies = [])
    {
        $context = SerializationContext::create();
        if (!empty($groups)) {
            $context->setGroups($groups);
        }

        //Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
            new PublishDetailsExclusionStrategy()
        );

        foreach ($customExclusionStrategies as $exclusionStrategy) {
            $context->addExclusionStrategy($exclusionStrategy);
        }

        //include null values
        $context->setSerializeNull(true);

        // serialize the data and send it as a payload
        return $this->serializer->serialize($payload, 'json', $context);
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'webhook:webhooks';
    }

    private function isProcessingExpired(): bool
    {
        $currentTime = microtime(true);
        $runTime     = $currentTime - $this->startTime;

        return $runTime >= $this->webhookTimeLimit;
    }

    /**
     * Sets all class properties from CoreParametersHelper.
     */
    private function setConfigProps(CoreParametersHelper $coreParametersHelper)
    {
        $this->webhookLimit     = (int) $coreParametersHelper->get('webhook_limit', 10);
        $this->webhookTimeLimit = (int) $coreParametersHelper->get('webhook_time_limit', 600);
        $this->disableLimit     = (int) $coreParametersHelper->get('webhook_disable_limit', 100);
        $this->webhookTimeout   = (int) $coreParametersHelper->get('webhook_timeout', 15);
        $this->logMax           = (int) $coreParametersHelper->get('webhook_log_max', 1000);
        $this->queueMode        = $coreParametersHelper->get('queue_mode');
        $this->eventsOrderByDir = $coreParametersHelper->get('events_orderby_dir', Criteria::ASC);
    }
}
