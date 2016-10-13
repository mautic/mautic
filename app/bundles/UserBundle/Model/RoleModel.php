<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Event\RoleEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

/**
 * Class RoleModel.
 */
class RoleModel extends FormModel
{
    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticUserBundle:Role');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'user:roles';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role'], 'Entity must be of class Role()');
        }

        $isNew = ($entity->getId()) ? 0 : 1;

        if (!$isNew) {
            //delete all existing
            $this->em->getRepository('MauticUserBundle:Permission')->purgeRolePermissions($entity);
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Generate the role's permissions.
     *
     * @param Role  $entity
     * @param array $rawPermissions (i.e. from request)
     */
    public function setRolePermissions(Role &$entity, $rawPermissions)
    {
        if (!is_array($rawPermissions)) {
            return;
        }

        //set permissions if applicable and if the user is not an admin
        $permissions = (!$entity->isAdmin() && !empty($rawPermissions)) ?
            $this->security->generatePermissions($rawPermissions) :
            [];

        foreach ($permissions as $permissionEntity) {
            $entity->addPermission($permissionEntity);
        }

        $entity->setRawPermissions($rawPermissions);
    }

    /**
     * {@inheritdoc}
     *
     * @throws PreconditionRequiredHttpException
     */
    public function deleteEntity($entity)
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role'], 'Entity must be of class Role()');
        }

        $users = $this->em->getRepository('MauticUserBundle:User')->findByRole($entity);
        if (count($users)) {
            throw new PreconditionRequiredHttpException(
                $this->translator->trans('mautic.user.role.error.deletenotallowed', ['%name%' => $entity->getName()], 'flashes')
            );
        }

        parent::deleteEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Role) {
            throw new MethodNotAllowedHttpException(['Role']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('role', $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Role();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
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
            if (empty($event)) {
                $event = new RoleEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }
}
