<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\MessageEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Model\AuditLogModel;

/**
 * Class MessageSubscriber.
 */
class MessageSubscriber extends CommonSubscriber
{
    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * DynamicContentSubscriber constructor.
     *
     * @param AuditLogModel $auditLogModel
     */
    public function __construct(
        AuditLogModel $auditLogModel
    ) {
        $this->auditLogModel = $auditLogModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::MESSAGE_POST_SAVE   => ['onPostSave', 0],
            ChannelEvents::MESSAGE_POST_DELETE => ['onDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param MessageEvent $event
     */
    public function onPostSave(MessageEvent $event)
    {
        $entity = $event->getMessage();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'   => 'channel',
                'object'   => 'message',
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
     * @param MessageEvent $event
     */
    public function onDelete(MessageEvent $event)
    {
        $entity = $event->getMessage();
        $log    = [
            'bundle'   => 'channel',
            'object'   => 'message',
            'objectId' => $entity->deletedId,
            'action'   => 'delete',
            'details'  => ['name' => $entity->getName()],
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
