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

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Joomla\Http\Http;
use Joomla\Http\Response;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\WebhookBundle\Entity\EventRepository;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\LogRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Event as Events;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel.
 */
class WebhookModel extends FormModel
{
    /**
     * Whet queue mode is turned on.
     *
     * @var string
     */
    protected $queueMode;

    /**
     * Deprecated property, should be 0 by default.
     *
     * @var int
     */
    protected $webhookStart;

    /**
     * How many entities to add into one queued webhook.
     *
     * @var int
     */
    protected $webhookLimit;

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
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * WebhookModel constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param Serializer           $serializer
     * @param NotificationModel    $notificationModel
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        Serializer $serializer,
        NotificationModel $notificationModel
    ) {
        $this->webhookStart      = (int) $coreParametersHelper->getParameter('webhook_start', 0);
        $this->webhookLimit      = (int) $coreParametersHelper->getParameter('webhook_limit', 10);
        $this->disableLimit      = (int) $coreParametersHelper->getParameter('webhook_disable_limit', 100);
        $this->webhookTimeout    = (int) $coreParametersHelper->getParameter('webhook_timeout', 15);
        $this->logMax            = (int) $coreParametersHelper->getParameter('webhook_log_max', 1000);
        $this->queueMode         = $coreParametersHelper->getParameter('queue_mode');
        $this->serializer        = $serializer;
        $this->notificationModel = $notificationModel;
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
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

        return $formFactory->create('webhook', $entity, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Webhook();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\WebhookBundle\Entity\WebhookRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Webhook');
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
        $results = $this->getEventRepository()->getEntitiesByEventType($type);

        return $results;
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
     * @param       $webhookEvents
     * @param       $payload
     * @param array $serializationGroups
     */
    public function queueWebhooks($webhookEvents, $payload, array $serializationGroups = [])
    {
        if (!count($webhookEvents) || !is_array($webhookEvents)) {
            return;
        }

        $webhookList = [];

        /** @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($webhookEvents as $event) {
            $webhook       = $event->getWebhook();
            $webhookList[] = $webhook;

            $webhook->addQueue($this->queueWebhook($webhook, $event, $payload, $serializationGroups));

            // add the queuelist and save everything
            $this->saveEntity($webhook);
        }

        if ($this->queueMode == 'immediate_process') {
            $this->processWebhooks($webhookList);
        }

        return;
    }

    /**
     * Creates a WebhookQueue entity, sets the date and returns the created entity.
     *
     * @param Webhook $webhook
     * @param         $event
     * @param         $payload
     * @param array   $serializationGroups
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
        foreach ($webhooks as $webhook) {
            $this->processWebhook($webhook);
        }
    }

    /**
     * @param Webhook $webhook
     *
     * @return bool
     */
    public function processWebhook(Webhook $webhook)
    {
        /** @var \Mautic\WebhookBundle\Entity\WebhookQueueRepository $webhookQueueRepo */
        $webhookQueueRepo = $this->getQueueRepository();

        // instantiate new http class
        $http = new Http();

        // get the webhook payload
        $payload = $this->getWebhookPayload($webhook);

        // if there wasn't a payload we can stop here
        if (empty($payload)) {
            return false;
        }

        if (is_array($payload)) {
            $payload = json_encode($payload);
        }

        // Set up custom headers
        $headers = ['Content-Type' => 'application/json'];
        $start   = microtime(true);

        try {
            $response = $http->post($webhook->getWebhookUrl(), $payload, $headers, $this->webhookTimeout);
            $this->addLog($webhook, $response->code, (microtime(true) - $start));

            // throw an error exception if we don't get a 200 back
            if ($response->code >= 300 && $response->code < 200) {
                throw new \ErrorException($webhook->getWebhookUrl().' returned '.$response->code);
            }
        } catch (\Exception $e) {
            $messsge = $e->getMessage();

            if ($this->isSick($webhook)) {
                $this->killWebhook($webhook);
                $messsge .= ' '.$this->translator->trans('mautic.webhook.killed', ['%limit%' => $this->disableLimit]);
            }

            // log any errors but allow the script to keep running
            $this->logger->addError($messsge);

            // log that the request failed to display it to the user
            $this->addLog($webhook, 'N/A', (microtime(true) - $start), $messsge);

            return false;
        }

        if ($webhook->getId()) {

            // delete all the queued items we just processed
            $webhookQueueRepo->deleteQueuesById($this->webhookQueueIdList);
            $queueCount = $webhookQueueRepo->getQueueCountByWebhookId($webhook->getId());

            // reset the array to blank so none of the IDs are repeated
            $this->webhookQueueIdList = [];

            // if there are still items in the queue after processing we re-process
            // WARNING: this is recursive
            if ($queueCount > 0) {
                $this->processWebhook($webhook);
            }
        }

        return true;
    }

    /**
     * Look into the history and check if all the responses we care about had failed.
     * But let it run for a while after the user modified it. Lets not aggravate the user.
     *
     * @param Webhook $webhook
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
        if ($successRadio === null) {
            return false;
        }

        return !$successRadio;
    }

    /**
     * Unpublish the webhook so it will stop emit the requests
     * and notify user about it.
     *
     * @param Webhook $webhook
     */
    public function killWebhook(Webhook $webhook)
    {
        $webhook->setIsPublished(false);
        $this->saveEntity($webhook);

        $this->notificationModel->addNotification(
            $this->translator->trans(
                'mautic.webhook.stopped.details',
                [
                    '%webhook%' => '<a href="'.$this->router->generate(
                        'mautic_webhook_action',
                        ['objectAction' => 'view', 'objectId' => $webhook->getId()]
                    ).'" data-toggle="ajax">'.$webhook->getName().'</a>',
                ]
            ),
            'error',
            false,
            $this->translator->trans('mautic.webhook.stopped')
        );
    }

    /**
     * Add a log for the webhook response HTTP status and save it.
     *
     * @param Webhook $webhook
     * @param int     $statusCode
     * @param float   $runtime    in seconds
     * @param string  $note
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
     * Get Qeueue Repository.
     *
     * @return WebhookQueueRepository
     */
    public function getQueueRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:WebhookQueue');
    }

    /**
     * @return EventRepository
     */
    public function getEventRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Event');
    }

    /**
     * @return LogRepository
     */
    public function getLogRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Log');
    }

    /**
     * Get the payload from the webhook.
     *
     * @param Webhook $webhook
     *
     * @return array
     */
    public function getWebhookPayload(Webhook $webhook)
    {
        if ($payload = $webhook->getPayload()) {
            return $payload;
        }

        $queuesArray = $this->getWebhookQueues($webhook);
        $payload     = [];

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

                $this->webhookQueueIdList[] = $queue->getId();
                $this->em->clear(WebhookQueue::class);
            }
        }

        return $payload;
    }

    /**
     * Get the queues and order by date so we get events in chronological order.
     *
     * @param Webhook $webhook
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getWebhookQueues(Webhook $webhook)
    {
        /** @var \Mautic\WebhookBundle\Entity\WebhookQueueRepository $queueRepo */
        $queueRepo = $this->getQueueRepository();

        $queues = $queueRepo->getEntities(
            [
                'iterator_mode' => true,
                'start'         => $this->webhookStart,
                'limit'         => $this->webhookLimit,
                'orderBy'       => 'e.dateAdded', // e is the default prefix unless you define getTableAlias in your repo class,
                'filter'        => [
                    'force' => [
                        [
                            'column' => 'IDENTITY(e.webhook)',
                            'expr'   => 'eq',
                            'value'  => $webhook->getId(),
                        ],
                    ],
                ],
            ]
        );

        return $queues;
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
     * @param array $customExclusionStrategies
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
}
