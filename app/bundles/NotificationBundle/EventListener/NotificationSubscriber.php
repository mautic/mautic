<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\NotificationBundle\Event\NotificationEvent;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private TrackableModel $trackableModel,
        private PageTokenHelper $pageTokenHelper,
        private AssetTokenHelper $assetTokenHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NotificationEvents::NOTIFICATION_POST_SAVE   => ['onPostSave', 0],
            NotificationEvents::NOTIFICATION_POST_DELETE => ['onDelete', 0],
            NotificationEvents::TOKEN_REPLACEMENT        => ['onTokenReplacement', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPostSave(NotificationEvent $event): void
    {
        $entity = $event->getNotification();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'   => 'notification',
                'object'   => 'notification',
                'objectId' => $entity->getId(),
                'action'   => ($event->isNew()) ? 'create' : 'update',
                'details'  => $details,
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onDelete(NotificationEvent $event): void
    {
        $entity = $event->getNotification();
        $log    = [
            'bundle'   => 'notification',
            'object'   => 'notification',
            'objectId' => $entity->deletedId,
            'action'   => 'delete',
            'details'  => ['name' => $entity->getName()],
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onTokenReplacement(TokenReplacementEvent $event): void
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($content) {
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough)
            );

            [$content, $trackables] = $this->trackableModel->parseContentForTrackables(
                $content,
                $tokens,
                'notification',
                $clickthrough['channel'][1]
            );

            /**
             * @var string    $token
             * @var Trackable $trackable
             */
            foreach ($trackables as $token => $trackable) {
                $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough);
            }

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);

            $event->setContent($content);
        }
    }
}
