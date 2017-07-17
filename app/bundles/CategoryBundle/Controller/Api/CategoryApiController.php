<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CategoryBundle\Entity\Category;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class CategoryApiController.
 */
class CategoryApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('category');
        $this->entityClass      = 'Mautic\CategoryBundle\Entity\Category';
        $this->entityNameOne    = 'category';
        $this->entityNameMulti  = 'categories';
        $this->serializerGroups = ['categoryDetails'];

        parent::initialize($event);
    }

    /**
     * Checks if user has permission to access retrieved entity.
     *
     * @param Category $entity
     * @param string   $action view|create|edit|publish|delete
     *
     * @return bool
     */
    protected function checkEntityAccess($entity, $action = 'view')
    {
        if (!$bundle = $entity->getBundle()) {
            $bundle = 'category';
        }

        $permissionBase = $this->permissionBase;

        if ($this->security->checkPermissionExists($bundle.':categories:'.$action)) {
            // An entity specific category permission rule exists
            $permissionBase = $bundle.':categories';
        }

        if ($action != 'create') {
            $ownPerm   = "$permissionBase:{$action}own";
            $otherPerm = "$permissionBase:{$action}other";

            return $this->security->hasEntityAccess($ownPerm, $otherPerm, $entity->getCreatedBy());
        }

        return $this->security->isGranted("$permissionBase:create");
    }

    /**
     * @return array
     */
    protected function getEntityFormOptions()
    {
        return ['show_bundle_select' => true];
    }
}
