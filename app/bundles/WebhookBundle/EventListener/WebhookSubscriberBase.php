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
use Mautic\WebhookBundle\Model\WebhookModel;

/**
 * Class WebhookSubscriberBase
 *
 * @package Mautic\Webhook\EventListener
 */
class WebhookSubscriberBase extends CommonSubscriber
{
    /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
    protected $webhookModel;

    /**
     * @param WebhookModel $webhookModel
     */
    public function setWebhookModel(WebhookModel $webhookModel)
    {
        $this->webhookModel = $webhookModel;
    }

    /**
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