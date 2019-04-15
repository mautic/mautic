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

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\WebhookEvents;
use Recurr\Transformer\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        IpLookupHelper $ipLookupHelper,
        AuditLogModel $auditLogModel,
        NotificationModel $notificationModel,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->ipLookupHelper    = $ipLookupHelper;
        $this->auditLogModel     = $auditLogModel;
        $this->notificationModel = $notificationModel;
        $this->entityManager     = $entityManager;
        $this->translator        = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WebhookEvents::WEBHOOK_POST_SAVE   => ['onWebhookSave', 0],
            WebhookEvents::WEBHOOK_POST_DELETE => ['onWebhookDelete', 0],
            WebhookEvents::WEBHOOK_KILL        => ['onWebhookKill', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
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

    /**
     * Send notification about killed webhook.
     */
    public function onWebhookKill(WebhookEvent $event)
    {
        $webhook = $event->getWebhook();
        $reason  = $event->getReason();

        $this->notificationModel->addNotification(
            $this->translator->trans(
                'mautic.webhook.stopped.details',
                [
                    '%reason%'  => $this->translator->trans($reason),
                    '%webhook%' => '<a href="'.$this->router->generate(
                            'mautic_webhook_action',
                            ['objectAction' => 'view', 'objectId' => $webhook->getId()]
                        ).'" data-toggle="ajax">'.$webhook->getName().'</a>',
                ]
            ),
            'error',
            false,
            $this->translator->trans('mautic.webhook.stopped'),
            null,
            null,
            $this->entityManager->getReference('MauticUserBundle:User', $webhook->getCreatedBy())
        );
    }
}
