<?php

namespace Mautic\WebhookBundle\Model;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

/**
 * @extends FormModel<Webhook>
 */
class WebhookModel extends FormModel
{
    /**
     *  2 possible types of the processing of the webhooks.
     */
    public const COMMAND_PROCESS   = 'command_process';

    public const IMMEDIATE_PROCESS = 'immediate_process';

    private const DELETE_BATCH_LIMIT = 5000;

    public const WEBHOOK_LOG_MAX = 1000;

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
     * Sets min webhook queue ID to get/process.
     */
    protected ?int $minQueueId = null;

    /**
     * Sets max webhook queue ID to get/process.
     */
    protected ?int $maxQueueId = null;

    /**
     * How long the webhook processing can run in seconds.
     */
    private int $webhookTimeLimit;

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
     * Queued events default order by dir
     * Possible values: ['ASC', 'DESC'].
     *
     * @var string
     */
    protected $eventsOrderByDir;

    /**
     * Timestamp when the webhook processing starts.
     */
    private ?float $startTime = null;

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        protected SerializerInterface $serializer,
        private Client $httpClient,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
    ) {
        $this->setConfigProps($coreParametersHelper);

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @param Webhook $entity
     */
    public function saveEntity($entity, $unlock = true): void
    {
        if (null === $entity->getSecret()) {
            $entity->setSecret(EncryptionHelper::generateKey());
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param Webhook      $entity
     * @param array<mixed> $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Webhook) {
            throw new MethodNotAllowedHttpException(['Webhook']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        $options['events'] = $this->getEvents();

        return $formFactory->create(WebhookType::class, $entity, $options);
    }

    public function getEntity($id = null): ?Webhook
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
            // build them
            $events = [];
            $event  = new Events\WebhookBuilderEvent($this->translator);
            $this->dispatcher->dispatch($event, WebhookEvents::WEBHOOK_ON_BUILD);
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

    public function queueWebhooksByType($type, $payload, array $groups = []): void
    {
        $this->queueWebhooks(
            $this->getEventWebooksByType($type),
            $payload,
            $groups
        );
    }

    public function queueWebhooks($webhookEvents, $payload, array $serializationGroups = []): void
    {
        if (!count($webhookEvents) || !is_array($webhookEvents)) {
            return;
        }

        /** @var Event $event */
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
     */
    public function queueWebhook(Webhook $webhook, $event, $payload, array $serializationGroups = []): WebhookQueue
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
            $this->dispatcher->dispatch($webhookQueueEvent, WebhookEvents::WEBHOOK_QUEUE_ON_ADD);
        }

        return $queue;
    }

    /**
     * Execute a list of webhooks to their specified endpoints.
     *
     * @param array|\Doctrine\ORM\Tools\Pagination\Paginator $webhooks
     */
    public function processWebhooks($webhooks): void
    {
        $this->startTime = microtime(true);

        foreach ($webhooks as $webhook) {
            $this->processWebhook($webhook);
        }
    }

    public function processWebhook(Webhook $webhook, WebhookQueue $queue = null): bool
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

            $this->addLog($webhook, $responseStatusCode, microtime(true) - $start, $responseBody);

            // throw an error exception if we don't get a 200 back
            if ($responseStatusCode >= 300 || $responseStatusCode < 200) {
                // The receiver of the webhook is telling us to stop bothering him with our requests by code 410
                if (410 === $responseStatusCode) {
                    $this->killWebhook($webhook, 'mautic.webhook.stopped.reason.410');
                }

                throw new \ErrorException($webhook->getWebhookUrl().' returned '.$responseStatusCode.' with payload: '.json_encode($payload));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if ($this->isSick($webhook)) {
                $this->killWebhook($webhook);
                $message .= ' '.$this->translator->trans('mautic.webhook.killed', ['%limit%' => $this->disableLimit]);
            }

            // log any errors but allow the script to keep running
            $this->logger->error($message);

            // log that the request failed to display it to the user
            $this->addLog($webhook, 'N/A', microtime(true) - $start, $message);

            return false;
        }

        // Run this on command as well as immediate send because if switched from queue to immediate
        // it can have some rows in the queue which will be send in every webhook forever
        if (!empty($this->webhookQueueIdList)) {
            // delete all the queued items we just processed
            $webhookQueueRepo->deleteQueuesById(array_keys($this->webhookQueueIdList));
            $nextWebhookExists = $webhookQueueRepo->exists($webhook->getId());

            // reset the array to blank so none of the IDs are repeated
            $this->webhookQueueIdList = [];

            // if there are still items in the queue after processing we re-process
            // WARNING: this is recursive
            if ($nextWebhookExists && !$this->isProcessingExpired()) {
                $this->processWebhook($webhook);
            }
        }

        return true;
    }

    /**
     * Look into the history and check if all the responses we care about had failed.
     * But let it run for a while after the user modified it. Lets not aggravate the user.
     */
    public function isSick(Webhook $webhook): bool
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
    public function killWebhook(Webhook $webhook, $reason = 'mautic.webhook.stopped.reason'): void
    {
        $webhook->setIsPublished(false);
        $this->saveEntity($webhook);

        $event = new WebhookEvent($webhook, false, $reason);
        $this->dispatcher->dispatch($event, WebhookEvents::WEBHOOK_KILL);
    }

    /**
     * Add a log for the webhook response HTTP status and save it.
     *
     * @param int    $statusCode
     * @param float  $runtime    in seconds
     * @param string $note
     *                           $runtime variable unit is in seconds
     */
    public function addLog(Webhook $webhook, $statusCode, $runtime, $note = null): void
    {
        if (!$webhook->getId()) {
            return;
        }

        if (!$this->coreParametersHelper->get('clean_webhook_logs_in_background')) {
            $this->getLogRepository()->removeLimitExceedLogs($webhook->getId(), $this->logMax);
        }

        $log = new Log();
        $log->setWebhook($webhook);
        $log->setNote($note);
        $log->setRuntime($runtime);
        $log->setStatusCode($statusCode);
        $log->setDateAdded(new \DateTime());
        $webhook->addLog($log);
        $this->saveEntity($webhook);
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
            $queuesArray = null !== $queue ? [$queue] : [];
        }

        /* @var WebhookQueue $queueItem */
        foreach ($queuesArray as $queueItem) {
            /** @var Event $event */
            $event = $queueItem->getEvent();
            $type  = $event->getEventType();

            // create new array level for each unique event type
            if (!isset($payload[$type])) {
                $payload[$type] = [];
            }

            $queuePayload              = json_decode($queueItem->getPayload(), true);
            $queuePayload['timestamp'] = $queueItem->getDateAdded()->format('c');

            // its important to decode the payload form the DB as we re-encode it with the
            $payload[$type][] = $queuePayload;

            // Add to the webhookQueueIdList only if ID exists.
            // That means if it was stored to DB and not sent via immediate send.
            if ($queueItem->getId()) {
                $this->webhookQueueIdList[$queueItem->getId()] = $queueItem;

                // Clear the WebhookQueue entity from memory
                $this->em->detach($queueItem);
            }
        }

        return $payload;
    }

    /**
     * Get the queues and order by date so we get events.
     *
     * @return iterable<object>
     */
    public function getWebhookQueues(Webhook $webhook)
    {
        /** @var WebhookQueueRepository $queueRepo */
        $queueRepo = $this->getQueueRepository();

        $parameters = [
            'iterable_mode' => true,
            'start'         => 0,
            'limit'         => $this->webhookLimit,
            'orderBy'       => $queueRepo->getTableAlias().'.id',
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
        ];

        if ($this->minQueueId && $this->maxQueueId) {
            unset($parameters['start']);
            unset($parameters['limit']);

            $parameters['filter']['force'][] = [
                'column' => $queueRepo->getTableAlias().'.id',
                'expr'   => 'gte',
                'value'  => $this->minQueueId,
            ];

            $parameters['filter']['force'][] = [
                'column' => $queueRepo->getTableAlias().'.id',
                'expr'   => 'lte',
                'value'  => $this->maxQueueId,
            ];
        }

        return $queueRepo->getEntities($parameters);
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
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, SymfonyEvent $event = null): ?SymfonyEvent
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
            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param array $groups
     */
    public function serializeData($payload, $groups = [], array $customExclusionStrategies = []): string
    {
        $context = SerializationContext::create();
        if (!empty($groups)) {
            $context->setGroups($groups);
        }

        // Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
            new PublishDetailsExclusionStrategy()
        );

        foreach ($customExclusionStrategies as $exclusionStrategy) {
            $context->addExclusionStrategy($exclusionStrategy);
        }

        // include null values
        $context->setSerializeNull(true);

        // serialize the data and send it as a payload
        return $this->serializer->serialize($payload, 'json', $context);
    }

    public function getPermissionBase(): string
    {
        return 'webhook:webhooks';
    }

    public function getWebhookLimit(): int
    {
        return $this->webhookLimit;
    }

    public function setMinQueueId(int $minQueueId): self
    {
        $this->minQueueId = $minQueueId;

        return $this;
    }

    public function setMaxQueueId(int $maxQueueId): self
    {
        $this->maxQueueId = $maxQueueId;

        return $this;
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
    private function setConfigProps(CoreParametersHelper $coreParametersHelper): void
    {
        $this->webhookLimit     = (int) $coreParametersHelper->get('webhook_limit', 10);
        $this->webhookTimeLimit = (int) $coreParametersHelper->get('webhook_time_limit', 600);
        $this->disableLimit     = (int) $coreParametersHelper->get('webhook_disable_limit', 100);
        $this->webhookTimeout   = (int) $coreParametersHelper->get('webhook_timeout', 15);
        $this->logMax           = (int) $coreParametersHelper->get('webhook_log_max', self::WEBHOOK_LOG_MAX);
        $this->queueMode        = $coreParametersHelper->get('queue_mode');
        $this->eventsOrderByDir = $coreParametersHelper->get('events_orderby_dir', Order::Ascending->value);
    }
}
