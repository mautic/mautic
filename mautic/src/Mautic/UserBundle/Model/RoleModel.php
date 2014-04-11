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
use Mautic\UserBundle\Entity\Role;

/**
 * Class RoleModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class RoleModel extends FormModel
{
    /**
     * @var string
     */
    protected $repository     = 'MauticUserBundle:Role';

    /**
     * @var string
     */
    protected $permissionBase = 'user:roles';

    /**
     * {@inheritdoc}
     *
     * @param Role  $entity
     * @param bool  $isNew
     * @param array $overrides
     */
    public function saveEntity($entity, $isNew = false, $overrides = array())
    {
        if (!$entity instanceof Role) {
            //@TODO add error message
            return 0;
        }

        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic.security')->isGranted('user:roles:'. $permissionNeeded)) {
            //@TODO add error message
            return 0;
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

        return parent::saveEntity($entity, $isNew, $overrides);
    }
}