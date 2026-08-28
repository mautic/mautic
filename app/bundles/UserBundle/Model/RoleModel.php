<?php

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\RoleEvent;
use Mautic\UserBundle\Form\Type\RoleType;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Role>
 */
final class RoleModel extends FormModel implements GlobalSearchInterface
{
    public static function getName(): string
    {
        return 'user.role';
    }

    private UserRepository $userRepository;

    private PermissionRepository $permissionRepository;

    private RoleRepository $roleRepository;

    #[Required]
    public function autowireRoleModel(
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository,
        UserRepository $userRepository,
    ): void {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
        $this->userRepository = $userRepository;
    }

    public function getRepository(): RoleRepository
    {
        return $this->roleRepository;
    }

    public function getPermissionBase(): string
    {
        return 'user:roles';
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function saveEntity($entity, bool $unlock = true): void
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role'], 'Entity must be of class Role()');
        }

        $isNew = ($entity->getId()) ? 0 : 1;

        if (!$isNew) {
            // delete all existing
            $this->permissionRepository->purgeRolePermissions($entity);
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Generate the role's permissions.
     *
     * @param array $rawPermissions (i.e. from request)
     */
    public function setRolePermissions(Role &$entity, $rawPermissions): void
    {
        if (!is_array($rawPermissions)) {
            return;
        }

        // set permissions if applicable and if the user is not an admin
        $permissions = (!$entity->isAdmin() && [] !== $rawPermissions) ?
            $this->security->generatePermissions($rawPermissions) :
            [];

        foreach ($permissions as $permissionEntity) {
            $entity->addPermission($permissionEntity);
        }

        $entity->setRawPermissions($rawPermissions);
    }

    /**
     * @throws PreconditionRequiredHttpException
     */
    public function deleteEntity($entity): void
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role'], 'Entity must be of class Role()');
        }

        $users = $this->userRepository->findByRole($entity);
        if (count($users)) {
            throw new PreconditionRequiredHttpException($this->translator->trans('mautic.user.role.error.deletenotallowed', ['%name%' => $entity->getName()], 'flashes'));
        }

        parent::deleteEntity($entity);
    }

    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(RoleType::class, $entity, $options);
    }

    public function getEntity($id = null): ?Role
    {
        if (null === $id) {
            return new Role();
        }

        return parent::getEntity($id);
    }

    public function cloneEntity(Role $source): Role
    {
        $clone = new Role();
        $clone->setName($this->translator->trans('mautic.user.role.clone.prefix', ['%name%' => $source->getName()], 'messages'));
        $clone->setDescription($source->getDescription());
        $clone->setIsAdmin($source->isAdmin());

        $rawPermissions = $source->getRawPermissions() ?? [];
        $clone->setRawPermissions($rawPermissions);

        return $clone;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, bool $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role'], 'Entity must be of class Role()');
        }

        switch ($action) {
            case 'pre_save':
                $name = UserEvents::ROLE_PRE_SAVE;
                break;
            case 'post_save':
                $name = UserEvents::ROLE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = UserEvents::ROLE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = UserEvents::ROLE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new RoleEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }
}
