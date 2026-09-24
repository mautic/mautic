<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Event\PostDeleteRoleEvent;
use Mautic\UserBundle\Event\PostDeleteUserEvent;
use Mautic\UserBundle\Event\PostSaveRoleEvent;
use Mautic\UserBundle\Event\PostSaveUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class UserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostSaveUserEvent::class   => ['onUserPostSave', 0],
            PostDeleteUserEvent::class => ['onUserDelete', 0],
            PostSaveRoleEvent::class   => ['onRolePostSave', 0],
            PostDeleteRoleEvent::class => ['onRoleDelete', 0],
        ];
    }

    /**
     * Add a user entry to the audit log.
     */
    public function onUserPostSave(PostSaveUserEvent $event): void
    {
        $user = $event->getUser();

        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'user',
                'object'    => 'user',
                'objectId'  => $user->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a user delete entry to the audit log.
     */
    public function onUserDelete(PostDeleteUserEvent $event): void
    {
        $user = $event->getUser();
        $log  = [
            'bundle'    => 'user',
            'object'    => 'user',
            'objectId'  => $user->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $user->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a role entry to the audit log.
     */
    public function onRolePostSave(PostSaveRoleEvent $event): void
    {
        $role = $event->getRole();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'user',
                'object'    => 'role',
                'objectId'  => $role->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a role delete entry to the audit log.
     */
    public function onRoleDelete(PostDeleteRoleEvent $event): void
    {
        $role = $event->getRole();
        $log  = [
            'bundle'    => 'user',
            'object'    => 'role',
            'objectId'  => $role->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $role->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
