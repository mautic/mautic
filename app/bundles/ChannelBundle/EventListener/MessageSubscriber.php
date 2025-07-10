<?php

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\MessageEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MessageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::MESSAGE_POST_SAVE   => ['onPostSave', 0],
            ChannelEvents::MESSAGE_POST_DELETE => ['onDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPostSave(MessageEvent $event): void
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
     */
    public function onDelete(MessageEvent $event): void
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
