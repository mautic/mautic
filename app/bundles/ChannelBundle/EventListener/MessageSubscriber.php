<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class MessageSubscriber
{
    public function __construct(
        private AuditLogModel $auditLogModel,
    ) {
    }

    /**
     * Add an entry to the audit log.
     */
    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onPostSave(EntityPostSave $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Message) {
            return;
        }
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
    #[AsEventListener(
        event: EntityPostDelete::class,
    )]
    public function onDelete(EntityPostDelete $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Message) {
            return;
        }
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
