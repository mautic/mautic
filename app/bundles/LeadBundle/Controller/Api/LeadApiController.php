<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use JMS\Serializer\SerializationContext;
use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LeadApiController
 *
 * @package Mautic\LeadBundle\Controller\Api
 */
class LeadApiController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->get('mautic.factory')->getModel('lead.lead');
        $this->entityClass     = 'Mautic\LeadBundle\Entity\Lead';
        $this->entityNameOne   = 'lead';
        $this->entityNameMulti = 'leads';
    }

    /**
     * Obtains a list of leads
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Obtains a list of leads",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|firstName|lastName|email|company|score|phone)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->container->get('mautic.security')->isGranted(
            array('lead:leads:viewown', 'lead:leads:viewother'),
            'MATCH_ONE'
        )) {
            return $this->accessDenied();
        }

        $args = array(
            'start'      => $this->request->query->get('start', 0),
            'limit'      => $this->request->query->get('limit', $this->get('mautic.factory')->getParameter('default_pagelimit')),
            'filter'     => $this->request->query->get('search', ''),
            'orderBy'    => $this->request->query->get('orderBy', ''),
            'orderByDir' => $this->request->query->get('orderByDir', 'ASC')
        );

        $results = $this->model->getEntities($args);
        //we have to convert them from paginated proxy functions to entities in order for them to be
        //returned by the serializer/rest bundle

        $view = $this->view(array($this->entityNameMulti => $results), Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(array('limited'));
        $view->setSerializationContext($context);
        return $this->handleView($view);
    }

    /**
     * Obtains a specific lead
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Obtains a specific lead",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the lead was not found"
     *   }
     * )
     *
     * @param int $id Lead ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (!$this->container->get('mautic.security')->hasEntityAccess(
            'lead:leads:viewown',
            'lead:leads:viewother',
            $entity
        )) {
            return $this->accessDenied();
        }

        if (!$entity instanceof $this->entityClass) {
            throw new NotFoundHttpException($this->get('translator')->trans('mautic.api.call.notfound'));
        }

        $view = $this->view(array($this->entityNameOne => $entity), Codes::HTTP_OK);

        $context = SerializationContext::create()->setGroups(array("limited"));
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Deletes a lead
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Deletes a lead",
     *   statusCodes = {
     *     200 = "Returned if successful",
     *   }
     * )
     *
     * @param int $id Lead ID
     * @return Response
     */
    public function deleteEntityAction($id)
    {
        $entity = $this->model->getEntity($id);

        if (!$this->container->get('mautic.security')->hasEntityAccess(
            'lead:leads:deleteown',
            'lead:leads:deleteother',
            $entity
        )) {
            return $this->accessDenied();
        }

        if ($entity !== null) {
            /**
            //set custom fields before deleting
            $fields = $entity->getFields();
            foreach ($fields as $f) {
                $entity->addCustomField($f->getField()->getAlias(), $f->getValue());
            }
            */
            $this->model->deleteEntity($entity);

            $view = $this->view(array($this->entityNameOne => $entity), Codes::HTTP_OK);
            $context = SerializationContext::create()->setGroups(array("limited"));
            $view->setSerializationContext($context);
            return $this->handleView($view);
        } else {
            throw new NotFoundHttpException($this->get('translator')->trans('mautic.api.call.notfound'));
        }
    }

    /**
     * Creates a new lead
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Creates a new lead",
     *   statusCodes = {
     *     200 = "Returned if successful",
     *     400 = "Returned if validation failed"
     *   },
     *   input = "lead",
     *   output = "Mautic\LeadBundle\Entity\Lead"
     * )
     */
    public function newEntityAction()
    {
        $entity = $this->model->getEntity();

        if (!$this->container->get('mautic.security')->isGranted('lead:leads:create')) {
            return $this->accessDenied();
        }

        $parameters = $this->request->request->all();
        $this->model->setFieldValues($entity, $parameters);

        $this->serializerGroups = array("limited");
        return $this->processForm($entity, $parameters, 'POST');
    }


    /**
     * Edits an existing lead or creates a new one on PUT if not found
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Edits an existing lead or creates a new one on PUT if not found",
     *   statusCodes = {
     *     200 = "Returned if successful edit",
     *     201 = "Returned if a new lead was created",
     *     400 = "Returned if validation failed"
     *   },
     *   parameters = {
     *
     *   },
     *   input = "lead",
     *   output = "Mautic\LeadBundle\Entity\Lead"
     * )
     *
     * @param int $id Lead ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws NotFoundHttpException
     */
    public function editEntityAction($id)
    {
        $entity     = $this->model->getEntity($id);
        $parameters = $this->request->request->all();
        $method     = $this->request->getMethod();

        if (!$this->container->get('mautic.security')->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $entity
        )) {
            return $this->accessDenied();
        }

        if ($entity === null) {
            if ($method === "PATCH" ||
                ($method === "PUT" && !$this->container->get('mautic.security')->isGranted('lead:leads:create'))
            ) {
                //PATCH requires that an entity exists or must have create access for PUT
                throw new NotFoundHttpException($this->get('translator')->trans('mautic.api.call.notfound'));
            } else {
                $entity = $this->model->getEntity();
            }
        }

        $this->model->setFieldValues($entity, $parameters);

        $this->serializerGroups = array("limited");
        return $this->processForm($entity, $parameters, $method);
    }

    /**
     * Add the customFields after edit/new
     *
     * @param $entity
     */
    protected function postProcessForm(&$entity)
    {
        //set custom fields before deleting
        $fields = $entity->getFields();
        foreach ($fields as $f) {
            $entity->addCustomField($f->getField()->getAlias(), $f->getValue());
        }
    }

    /**
     * Obtains a list of users for lead owner edits
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Obtains a list of available users(owners) for leads",
     *   filters={
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *   },
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOwnersAction()
    {
        if (!$this->container->get('mautic.security')->isGranted(
            array('lead:leads:create', 'lead:leads:editown', 'lead:leads:editother'),
            'MATCH_ONE'
        )) {
            return $this->accessDenied();
        }

        $filter = $this->request->query->get('filter', null);
        $limit  = $this->request->query->get('limit', null);
        $users  = $this->model->getLookupResults('user', $filter, $limit);

        $view = $this->view($users, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(array('limited'));
        $view->setSerializationContext($context);
        return $this->handleView($view);
    }

    /**
     * Obtains a list of smart lists for the user
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Obtains a list of of smart lists for the user",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getListsAction()
    {
        $lists = $this->get('mautic.factory')->getModel('lead.list')->getSmartLists();
        $view = $this->view($lists, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(array('limited'));
        $view->setSerializationContext($context);
        return $this->handleView($view);
    }

    /**
     * Obtains a list of custom fields
     *
     * @ApiDoc(
     *   section = "Leads",
     *   description = "Obtains a list of of custom fields for editing leads",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getFieldsAction()
    {
        if (!$this->container->get('mautic.security')->isGranted(
            array('lead:leads:editown','lead:leads:editother'),
            'MATCH_ONE'
        )) {
            return $this->accessDenied();
        }

        $fields = $this->get('mautic.factory')->getModel('lead.field')->getEntities();
        $view = $this->view($fields, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(array('limited'));
        $view->setSerializationContext($context);
        return $this->handleView($view);
    }
}