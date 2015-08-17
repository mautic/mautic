<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\Webhook\EventListener
 */
class WebhookSubscriberBase extends CommonSubscriber
{
    /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
    protected $webhookModel;

    public function __construct(MauticFactory $factory)
    {
        parent::__construct($factory);
        $this->webhookModel = $this->factory->getModel('webhook.webhook');
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents() {
        return array();
    }

    /*
     * Look up list of webhooks using the event type as an identifer
     *
     * @param $type string
     *
     * @return array
     */
    public function getEventWebooksByType($type)
    {
        $eventWebhooks = $this->webhookModel->getEventWebooksByType($type);

        return $eventWebhooks;
    }
}