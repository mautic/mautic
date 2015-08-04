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
use Mautic\WebhookBundle\Event as Events;
use Mautic\WebhookBundle\WebhookEvents;
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

        var_dump($events);
        exit();

        return $events;
    }
}