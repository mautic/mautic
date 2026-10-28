<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\MessageEvent;
use Mautic\CoreBundle\Model\AuditLogModel;

final readonly class MessageSubscriber
{
    public function __construct(
        private AuditLogModel $auditLogModel,
    ) {
    }
    /**
     * Add an entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ChannelEvents::MESSAGE_POST_SAVE,
        priority: 0
    )]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ChannelEvents::MESSAGE_POST_DELETE,
        priority: 0
    )]
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
