<?php

namespace Mautic\UserBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\UserBundle\Entity\Role;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

/**
 * Class RoleApiController.
 */
class RoleApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(ControllerArgumentsEvent $event)
    {
        $this->model            = $this->getModel('user.role');
        $this->entityClass      = 'Mautic\UserBundle\Entity\Role';
        $this->entityNameOne    = 'role';
        $this->entityNameMulti  = 'roles';
        $this->serializerGroups = ['roleDetails', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * {@inheritdoc}
     *
     * @param Role                                 $entity
     * @param ?array<int|string|array<int|string>> $parameters
     * @param Form                                 $form
     * @param string                               $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (isset($parameters['rawPermissions'])) {
            $this->model->setRolePermissions($entity, $parameters['rawPermissions']);
        }
    }
}
