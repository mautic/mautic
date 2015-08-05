<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Event as Events;
use Mautic\WebhookBundle\WebhookEvents;
use OpenCloud\Common\Exceptions\DatabaseCreateError;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel
 */
class WebhookModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $params = array())
    {
        if (!$entity instanceof Webhook) {
            throw new MethodNotAllowedHttpException (array('Webhook'));
        }

        if (!empty($action)) {
            $params['action']  = $action;
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
    public function getRepository ()
    {
        return $this->em->getRepository('MauticWebhookBundle:Webhook');
    }

    /**
     * Gets array of custom events from bundles subscribed MauticWehbhookBundle::WEBHOOK_ON_BUILD
     *
     * @return mixed
     */
    public function getEvents ()
    {
        static $events;

        if (empty($events)) {
            //build them
            $events = array();
            $event  = new Events\WebhookBuilderEvent($this->translator);
            $this->dispatcher->dispatch(WebhookEvents::WEBHOOK_ON_BUILD, $event);
            $events = $event->getEvents();
        }

        return $events;
    }

    /*
     *
     */
    public function getWebhooksByEventTypes(array $types)
    {
        $results = $this->getRepository()->getEntitiesByEventTypes($types);
        return $results;
    }

    /*
     * Takes an array of webhooks and adds them to the webhook queue so they can be processed.abstract
     *
     * Optionally returns an array of all the queue IDs created so they can be immediately executed.
     *
     * @param $webhooks array
     * @param $returnQueueEntities bool
     *
     * @return array
     */
    public function QueueWebhooks($webhooks, $returnQueueEntities = false)
    {
        if (! count($webhooks)) {
            return;
        }

        $queueIds = array();
        $queueList = array();

        /** @var \Mautic\WebhookBundle\Entity\Webhook $webhook */
        foreach ($webhooks as $webhook)
        {
            $queueEntity = $this->queueWebhook($webhook);
            $queueList[] = $queueEntity;

            // if we need to return the queues back to whatever called this record the id
            if ($returnQueueEntities) {
                $queueIds[] = $queueEntity->getId();
            }
        }

        // add the queuelist and save everything
        $webhook->addQueues($queueList);
        $this->saveEntity($webhook);

        // this will either be a blank array or an array of Ids.
        return $queueIds;
    }

    /*
     * Creates a WebhookQueue entity, sets the date and returns the created entity
     *
     * @param  $webhook Webhook
     *
     * @return WebhookQueue
     */
    public function queueWebhook(Webhook $webhook)
    {
        $queue = new WebhookQueue();
        $queue->setWebhook($webhook);
        $queue->setDateAdded(new \DateTime);

        return $queue;
    }
}