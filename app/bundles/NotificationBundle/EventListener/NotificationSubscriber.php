<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\NotificationBundle\Event\NotificationEvent;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;

/**
 * Class NotificationSubscriber.
 */
class NotificationSubscriber extends CommonSubscriber
{
    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var PageTokenHelper
     */
    protected $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    protected $assetTokenHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * NotificationSubscriber constructor.
     *
     * @param AuditLogModel    $auditLogModel
     * @param TrackableModel   $trackableModel
     * @param PageTokenHelper  $pageTokenHelper
     * @param AssetTokenHelper $assetTokenHelper
     */
    public function __construct(AuditLogModel $auditLogModel, TrackableModel $trackableModel, PageTokenHelper $pageTokenHelper, AssetTokenHelper $assetTokenHelper)
    {
        $this->auditLogModel    = $auditLogModel;
        $this->trackableModel   = $trackableModel;
        $this->pageTokenHelper  = $pageTokenHelper;
        $this->assetTokenHelper = $assetTokenHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NotificationEvents::NOTIFICATION_POST_SAVE   => ['onPostSave', 0],
            NotificationEvents::NOTIFICATION_POST_DELETE => ['onDelete', 0],
            NotificationEvents::TOKEN_REPLACEMENT        => ['onTokenReplacement', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param NotificationEvent $event
     */
    public function onPostSave(NotificationEvent $event)
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
     *
     * @param NotificationEvent $event
     */
    public function onDelete(NotificationEvent $event)
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

    /**
     * @param TokenReplacementEvent $event
     */
    public function onTokenReplacement(TokenReplacementEvent $event)
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

            list($content, $trackables) = $this->trackableModel->parseContentForTrackables(
                $content,
                $tokens,
                'notification',
                $clickthrough['channel'][1]
            );

            /**
             * @var string
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
