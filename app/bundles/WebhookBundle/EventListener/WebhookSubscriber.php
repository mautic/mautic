<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\Event\WebhookKillEvent;
use Mautic\WebhookBundle\Notificator\WebhookKillNotificator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class WebhookSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private WebhookKillNotificator $webhookKillNotificator,
    ) {
    }

    /**
     * Add an entry to the audit log.
     */
    #[AsEventListener]
    public function onWebhookSave(EntityPostSave $event): void
    {
        $webhook = $event->getEntity();
        if (!$webhook instanceof Webhook) {
            return;
        }

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
     */
    #[AsEventListener]
    public function onWebhookDelete(EntityPostDelete $event): void
    {
        $webhook = $event->getEntity();
        if (!$webhook instanceof Webhook) {
            return;
        }

        $log = [
            'bundle'    => 'webhook',
            'object'    => 'webhook',
            'objectId'  => $webhook->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $webhook->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Send notification about killed webhook.
     */
    #[AsEventListener(
        event: WebhookKillEvent::class,
    )]
    public function onWebhookKill(WebhookEvent $event): void
    {
        $this->webhookKillNotificator->send($event->getWebhook(), $event->getReason());
    }
}
