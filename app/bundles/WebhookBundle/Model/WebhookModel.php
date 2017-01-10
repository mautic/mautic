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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
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
    protected $queueMode;
    protected $webhookStart;
    protected $webhookLimit;
    protected $webhookQueueIdList = [];
    protected $logMax;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * WebhookModel constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param Serializer           $serializer
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, Serializer $serializer)
    {
        $this->queueMode    = $coreParametersHelper->getParameter('queue_mode');
        $this->webhookStart = $coreParametersHelper->getParameter('webhook_start');
        $this->webhookLimit = $coreParametersHelper->getParameter('webhook_limit');
        $this->serializer   = $serializer;
        $this->logMax       = $coreParametersHelper->getParameter('webhook_log_max', 10);
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

    /*
     * Get a list of webhooks by matching events
     *
     * @param $types string of event type
     *
     * @return array
     */
    public function getEventWebooksByType($type)
    {
        $results = $this->getEventRepository()->getEntitiesByEventType($type);

        return $results;
    }

    /*
     * Takes an array of webhooks and adds them to the webhook queue so they can be processed.abstract
     *
     * Optionally returns an array of all the queue IDs created so they can be immediately executed.
     *
     * @param $webhooks array of Webhook
     * @param $payload  Entity
     * @param $serializationGroups groups for the serializer
     * @param $returnQueueEntities bool
     *
     * @return
     */
    public function QueueWebhooks($webhookEvents, $payload, array $serializationGroups = [], $immediatelyExecuteWebhooks = false)
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

    /*
     * Creates a WebhookQueue entity, sets the date and returns the created entity
     *
     * @param  $webhook Webhook
     * @param  $eventId the id of th event that added this queue
     * @param  $payload Entity object
     * @param  $serializationGroups if the entity has some groups for serialization add them here.
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

    /*
     * Execute a list of webhooks to their specified endpoints
     *
     * @param array $webhooks
     */
    public function processWebhooks($webhooks)
    {
        foreach ($webhooks as $webhook) {
            $this->processWebhook($webhook);
        }
    }

    /*
     * Execute a single webhook post
     *
     * @param Webhook $webhook
     * @var   Http    $http
     */
    public function processWebhook(Webhook $webhook)
    {
        /** @var \Mautic\WebhookBundle\Entity\WebhookQueueRepository $webhookQueueRepo */
        $webhookQueueRepo = $this->getQueueRepository();

        // instantiate new http class
        $http = new Http();

        // get the webhook payload
        $payload = ($this->getWebhookPayload($webhook));

        // if there wasn't a payload we can stop here
        if (!count($payload)) {
            return;
        }

        // Set up custom headers
        $headers = ['Content-Type' => 'application/json'];

        /* @var \Mautic\WebhookBundle\Entity\Webhook $webhook */
        try {
            /** @var \Joomla\Http\Http $http */
            $response = $http->post($webhook->getWebhookUrl(), json_encode($payload), $headers);
            $this->addLog($webhook, $response);
            // throw an error exception if we don't get a 200 back
            if ($response->code != 200) {
                throw new \ErrorException($webhook->getWebhookUrl().' returned '.$response->code);
            }
        } catch (\Exception $e) {
            // log any errors but allow the script to keep running
            $this->logger->addError($e->getMessage());
        }

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

    /*
     * Add a log for the webhook response and save it
     */
    public function addLog(Webhook $webhook, Response $response)
    {
        $this->getLogRepository()->removeOldLogs($webhook->getId(), $this->logMax);
        $log = new Log();

        $log->setWebhook($webhook);
        $log->setStatusCode($response->code);
        $log->setDateAdded(new \DateTime());
        $webhook->addLog($log);

        $this->saveEntity($webhook);
    }

    /*
     * Get Qeueue Repository
     *
     * @return WebhookQueueRepository
     */
    public function getQueueRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:WebhookQueue');
    }

    /*
    * Get Event Repository
    */
    public function getEventRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Event');
    }

    /*
     * Return the log repo
     */
    public function getLogRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Log');
    }

    /*
     * Get the payload from the webhook
     *
     * @param Webhook $webhook
     *
     * @return array
     */
    public function getWebhookPayload($webhook)
    {
        $queuesArray = $this->getWebhookQueues($webhook);
        $payload     = [];

        /* @var \Mautic\WebhookBundle\Entity\WebhookQueue $queue */
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
                $this->em->clear($queue);
            }
        }

        return $payload;
    }

    /*
     * Get the queues and order by date so we get events in chronological order
     *
     * @return array
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
     * Serialize Data.
     */
    public function serializeData($payload, $groups = [])
    {
        $context = SerializationContext::create();
        if (!empty($groups)) {
            $context->setGroups($groups);
        }

        //Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
        // Can Use this; just adding full namespace to show
            new \Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy()
        );

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
