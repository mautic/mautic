<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\UserBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class RoleApiController.
 */
class RoleApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('user.role');
        $this->entityClass      = 'Mautic\UserBundle\Entity\Role';
        $this->entityNameOne    = 'role';
        $this->entityNameMulti  = 'roles';
        $this->permissionBase   = 'user:roles';
        $this->serializerGroups = ['roleDetails', 'publishDetails'];
    }

    /**
     * Obtains a list of roles.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific role.
     *
     * @param int $id Role ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }
}
