<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller\User;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserController
 *
 * @package Mautic\ApiBundle\Controller\User
 */
class UserController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->container->get('mautic.model.user');
        $this->entityClass     = 'Mautic\UserBundle\Entity\User';
        $this->entityNameOne   = 'user';
        $this->entityNameMulti = 'users';
    }

    /**
     * Obtains a list of users
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Obtains a list of users",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|username|firstName|lastName|email|role)", "description"="Table column in which to sort the results by."},
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
     * Obtains a specific user
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Obtains a specific user",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the user was not found"
     *   }
     * )
     *
     * @param int $id User ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }

    /**
     * Deletes a user
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Deletes a user",
     *   statusCodes = {
     *     200 = "Returned if successful",
     *   }
     * )
     *
     * @param int $id User ID
     * @return Response
     */
    public function deleteEntityAction($id)
    {
       return parent::deleteEntityAction($id);
    }

    /**
     * Creates a new user
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Creates a new user",
     *   statusCodes = {
     *     200 = "Returned if successful",
     *     400 = "Returned if validation failed"
     *   },
     *   input = "user",
     *   output = "Mautic\UserBundle\Entity\User"
     * )
     */
    public function newEntityAction()
    {
        $entity            = $this->model->getEntity();
        $parameters        = $this->request->request->all();

        if (isset($parameters['plainPassword']['password'])) {
            $submittedPassword = $parameters['plainPassword']['password'];
            $entity->setPassword($this->model->checkNewPassword($entity, $submittedPassword));
        }

        return $this->processForm($entity, $parameters, 'POST');
    }


    /**
     * Edits an existing user or creates a new one on PUT if not found
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Edits an existing user or creates a new one on PUT if not found",
     *   statusCodes = {
     *     200 = "Returned if successful edit",
     *     201 = "Returned if a new user was created",
     *     400 = "Returned if validation failed"
     *   },
     *   parameters = {
     *
     *   },
     *   input = "user",
     *   output = "Mautic\UserBundle\Entity\User"
     * )
     *
     * @param int $id User ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws NotFoundHttpException
     */
    public function editEntityAction($id)
    {
        $entity     = $this->model->getEntity($id);
        $parameters = $this->request->request->all();
        $method     = $this->request->getMethod();

        if (!$entity) {
            if ($method === "PATCH") {
                //PATCH requires that an entity exists
                throw new NotFoundHttpException($this->get('translator')->trans('mautic.api.call.notfound'));
            } else {
                //PUT can create a new entity if it doesn't exist
                $entity = $this->model->getEntity();

                if (isset($parameters['plainPassword']['password'])) {
                    $submittedPassword = $parameters['plainPassword']['password'];
                    $entity->setPassword($this->model->checkNewPassword($entity, $submittedPassword));
                }
            }
        } else {
            //Changing passwords via API is forbidden
            //@TODO reconsider username/password change restriction via API?
            if (!empty($parameters['plainPassword'])) {
                unset($parameters['plainPassword']);
            }
            if ($method == "PATCH") {
                //PATCH will accept a diff so just remove the entities

                //Changing username via API is forbidden
                if (!empty($parameters['username'])) {
                    unset($parameters['username']);
                }

                //Changing the role via the API is forbidden
                if (!empty($parameters['role'])) {
                    unset($parameters['role']);
                }

            } else {
                //PUT requires the entire entity so overwrite the username with the original
                $parameters['username'] = $entity->getUsername();
                $parameters['role']     = $entity->getRole()->getId();
            }
        }
        return $this->processForm($entity, $parameters, $method);
    }

    /**
     * Verifies if a user has permission(s) to a action
     *
     * @ApiDoc(
     *   section = "Users",
     *   description = "Verifies if a user has permission(s) to a action",
     *   statusCodes = {
     *     200 = "Returned if the permissions were found",
     *     400 = "Returned if a list of permissions was not included in the data",
     *     401 = "Returned if the user was not found"
     *   }
     * )
     *
     * @param int $id User ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
     */
    public function isGrantedAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (!$entity instanceof $this->entityClass) {
            throw new NotFoundHttpException($this->get('translator')->trans('mautic.api.call.notfound'));
        }

        $permissions = $this->request->request->get('permissions');

        if (empty($permissions)) {
            throw new BadRequestHttpException($this->get('translator')->trans('mautic.api.call.permissionempty'));
        } elseif (!is_array($permissions)) {
            $permissions = array($permissions);
        }

        $return = $this->get('mautic.security')->isGranted($permissions, "RETURN_ARRAY", $entity);
        $view = $this->view($return, Codes::HTTP_OK);
        return $this->handleView($view);
    }
}