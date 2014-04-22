<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Event\RoleEvent;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


/**
 * Class RoleModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class RoleModel extends FormModel
{

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->repository     = 'MauticUserBundle:Role';
        $this->permissionBase = 'user:roles';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param array $overrides
     * @return int
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function saveEntity($entity, $overrides = array())
    {
        if (!$entity instanceof Role) {
            throw new NotFoundHttpException('Entity must be of class Role()');
        }
        $isNew            = ($entity->getId()) ? 0 : 1;
        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic.security')->isGranted('user:roles:'. $permissionNeeded)) {
            throw new AccessDeniedException($this->container->get('translator')->trans('mautic.core.accessdenied'));
        }

        if (!$isNew) {
            //delete all existing
            $this->em->getRepository('MauticUserBundle:Permission')->purgeRolePermissions($entity);
        }

        //build the new permissions
        $formPermissionData = $this->request->request->get('role[permissions]', null, true);
        //set permissions if applicable and if the user is not an admin
        $permissions = (!empty($formPermissionData) && !$this->request->request->get('role[isAdmin]', 0, true)) ?
            $this->container->get('mautic.security')->generatePermissions($formPermissionData) :
            array();

        foreach ($permissions as $permissionEntity) {
            $entity->addPermission($permissionEntity);
        }

        return parent::saveEntity($entity, $overrides);
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entityId
     * @param bool $skipSecurity
     * @return null|object
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteEntity($entityId, $skipSecurity = false)
    {
        if (!$skipSecurity && !$this->container->get('mautic.security')->isGranted($this->permissionBase . ':deleteother')) {
            throw new AccessDeniedException($this->container->get('translator')->trans('mautic.core.accessdenied'));
        }

        $entity = $this->em->getRepository($this->repository)->find($entityId);

        if (count($entity->getUsers())) {
            throw new PreconditionRequiredHttpException(
                $this->container->get('translator')->trans(
                    'mautic.user.role.error.deletenotallowed',
                    array(),
                    'flashes'
                )
            );
        }


        //Event must be called first in order for getId() to be available for events
        $this->dispatchEvent("delete", $entity);

        $this->em->getRepository($this->repository)->deleteEntity($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param null $action
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $action = null)
    {
        if (!$entity instanceof Role) {
            throw new NotFoundHttpException('Entity must be of class Role()');
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        return $this->container->get('form.factory')->create('role', $entity, $params);
    }


    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = '')
    {
        if (empty($id)) {
            return new Role();
        }

        return parent::getEntity($id);
    }


    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\NotFoundHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false)
    {
        if (!$entity instanceof Role) {
            throw new NotFoundHttpException('Entity must be of class Role()');
        }

        $dispatcher = $this->container->get('event_dispatcher');
        $event      = new RoleEvent($entity, $isNew);

        switch ($action) {
            case "pre_save":
                $dispatcher->dispatch(UserEvents::ROLE_PRE_SAVE, $event);
                break;
            case "post_save":
                $dispatcher->dispatch(UserEvents::ROLE_POST_SAVE, $event);
                break;
            case "delete":
                $dispatcher->dispatch(UserEvents::ROLE_DELETE, $event);
                break;
        }
    }
}