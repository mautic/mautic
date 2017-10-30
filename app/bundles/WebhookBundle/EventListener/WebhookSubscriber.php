<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\WebhookEvents as WebhookEvents;

/**
 * Class WebhookSubscriber.
 */
class WebhookSubscriber extends CommonSubscriber
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * WebhookSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel  = $auditLogModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WebhookEvents::WEBHOOK_POST_SAVE   => ['onWebhookSave', 0],
            WebhookEvents::WEBHOOK_POST_DELETE => ['onWebhookDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param WebhookEvent $event
     */
    public function onWebhookSave(WebhookEvent $event)
    {
        $webhook = $event->getWebhook();

        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'webhook',
                'object'    => 'webhook',
                'objectId'  => $webhook->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param WebhookEvent $event
     */
    public function onWebhookDelete(WebhookEvent $event)
    {
        $webhook = $event->getWebhook();
        $log     = [
            'bundle'    => 'webhook',
            'object'    => 'webhook',
            'objectId'  => $event->getWebhook()->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $webhook->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
