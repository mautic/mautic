<?php

namespace Mautic\UserBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Role>
 */
class RoleApiController extends CommonApiController
{
    /**
     * @var RoleModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $roleModel = $this->getModel('user.role');

        if (!$roleModel instanceof RoleModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model            = $roleModel;
        $this->entityClass      = Role::class;
        $this->entityNameOne    = 'role';
        $this->entityNameMulti  = 'roles';
        $this->serializerGroups = ['roleDetails', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\Lead &$entity
     * @param                                $parameters
     * @param                                $form
     * @param string                         $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (isset($parameters['rawPermissions'])) {
            $this->model->setRolePermissions($entity, $parameters['rawPermissions']);
        }
    }
}
