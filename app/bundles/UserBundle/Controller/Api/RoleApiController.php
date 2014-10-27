<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class RoleApiController
 */
class RoleApiController extends CommonApiController
{

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->factory->getModel('user.role');
        $this->entityClass     = 'Mautic\UserBundle\Entity\Role';
        $this->entityNameOne   = 'role';
        $this->entityNameMulti = 'roles';
        $this->permissionBase  = 'user:roles';
        $this->serializerGroups = array('roleDetails', 'publishDetails');
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
        return parent::getEntityAction($id);
    }
}
