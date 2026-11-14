<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\PostSaveUserEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class UserSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onEntityPostSave(EntityPostSave $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof User) {
            $this->logUserSave($entity, $event->isNew(), $event->getChanges());
        } elseif ($entity instanceof Role) {
            $this->logRoleSave($entity, $event->isNew(), $event->getChanges());
        }
    }

    // UserProvider saves users outside the model, so it keeps dispatching the dedicated event.
    #[AsEventListener(
        event: PostSaveUserEvent::class,
    )]
    public function onUserPostSave(PostSaveUserEvent $event): void
    {
        $this->logUserSave($event->getUser(), $event->isNew(), $event->getChanges());
    }

    #[AsEventListener(
        event: EntityPostDelete::class,
    )]
    public function onEntityPostDelete(EntityPostDelete $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof User) {
            $this->logUserDelete($entity);
        } elseif ($entity instanceof Role) {
            $this->logRoleDelete($entity);
        }
    }

    /**
     * @param mixed $changes
     */
    private function logUserSave(User $user, bool $isNew, $changes): void
    {
        if (!$changes) {
            return;
        }

        $this->auditLogModel->writeToLog([
            'bundle'    => 'user',
            'object'    => 'user',
            'objectId'  => $user->getId(),
            'action'    => $isNew ? 'create' : 'update',
            'details'   => $changes,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }

    /**
     * @param mixed $changes
     */
    private function logRoleSave(Role $role, bool $isNew, $changes): void
    {
        if (!$changes) {
            return;
        }

        $this->auditLogModel->writeToLog([
            'bundle'    => 'user',
            'object'    => 'role',
            'objectId'  => $role->getId(),
            'action'    => $isNew ? 'create' : 'update',
            'details'   => $changes,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }

    private function logUserDelete(User $user): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'user',
            'object'    => 'user',
            'objectId'  => $user->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $user->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }

    private function logRoleDelete(Role $role): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'user',
            'object'    => 'role',
            'objectId'  => $role->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $role->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
