<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller\User;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class RoleController
 *
 * @package Mautic\ApiBundle\Controller\Role
 */
class RoleController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->get('mautic.model.role');
        $this->entityClass     = 'Mautic\UserBundle\Entity\Role';
        $this->entityNameOne   = 'role';
        $this->entityNameMulti = 'roles';
    }

    /**
     * Obtains a list of roles
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Obtains a list of roles",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|name)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->container->get('mautic.security')->isGranted('user:roles:view')) {
            return $this->accessDenied();
        }
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific role
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Obtains a specific role",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the role was not found"
     *   }
     * )
     *
     * @param int $id Role ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        if (!$this->container->get('mautic.security')->isGranted('user:roles:view')) {
            return $this->accessDenied();
        }
        return parent::getEntityAction($id);
    }

    /**
     * Delete is not allowed via API
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteEntityAction($id)
    {
        return $this->accessDenied();
    }

    /**
     * Editing roles is not allowed via API
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editEntityAction($id)
    {
        return $this->accessDenied();
    }

    /**
     * Adding roles is not allowed via API
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newEntityAction()
    {
        return $this->accessDenied();
    }
}