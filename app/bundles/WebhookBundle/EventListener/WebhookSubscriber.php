<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\WebhookBundle\WebhookEvents as WebhookEvents;
use Mautic\WebhookBundle\Event\WebhookEvent;

/**
 * Class WebhookSubscriber
 */
class WebhookSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            WebhookEvents::WEBHOOK_POST_SAVE       => array('onWebhookSave', 0),
            WebhookEvents::WEBHOOK_POST_DELETE     => array('onWebhookDelete', 0)
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param WebhookEvent $event
     */
    public function onWebhookSave(WebhookEvent $event)
    {
        $webhook = $event->getWebhook();

        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "webhook",
                "object"    => "webhook",
                "objectId"  => $webhook->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->factory->getIpAddressFromRequest()
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param WebhookEvent $event
     */
    public function onWebhookDelete(WebhookEvent $event)
    {
        $webhook = $event->getWebhook();
        $log = array(
            "bundle"     => "webhook",
            "object"     => "webhook",
            "objectId"   => $event->getWebhook()->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $webhook->getName()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}