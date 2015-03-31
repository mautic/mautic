<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use JMS\Serializer\SerializationContext;
use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LeadApiController
 *
 * @package Mautic\LeadBundle\Controller\Api
 */
class LeadApiController extends CommonApiController
{

    public function initialize (FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->factory->getModel('lead.lead');
        $this->entityClass      = 'Mautic\LeadBundle\Entity\Lead';
        $this->entityNameOne    = 'lead';
        $this->entityNameMulti  = 'leads';
        $this->permissionBase   = 'lead:leads';
        $this->serializerGroups = array("leadDetails", "userList", "publishDetails", "ipAddress");
    }

    /**
     * Obtains a list of leads
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction ()
    {
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific lead
     *
     * @param int $id Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction ($id)
    {
        return parent::getEntityAction($id);
    }

    /**
     * Deletes a lead
     *
     * @param int $id Lead ID
     *
     * @return Response
     */
    public function deleteEntityAction ($id)
    {
        return parent::deleteEntityAction($id);
    }

    /**
     * Creates a new lead.  You should make a call to /api/leads/list/fields in order to get a list of custom fields that will be accepted. The key should be the alias of the custom field. You can also pass in a ipAddress parameter if the IP of the lead is different than that of the originating request.
     */
    public function newEntityAction ()
    {
        return parent::newEntityAction();
    }

    /**
     * Edits an existing lead or creates a new one on PUT if not found.  You should make a call to /api/leads/list/fields in order to get a list of custom fields that will be accepted. The key should be the alias of the custom field. You can also pass in a ipAddress parameter if the IP of the lead is different than that of the originating request.
     *
     * @param int $id Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws NotFoundHttpException
     */
    public function editEntityAction ($id)
    {
        return parent::editEntityAction($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     *
     * @return mixed|void
     */
    protected function createEntityForm ($entity)
    {
        $fields = $this->factory->getModel('lead.field')->getEntities(array(
            'force'          => array(
                array(
                    'column' => 'f.isPublished',
                    'expr'   => 'eq',
                    'value'  => true
                )
            ),
            'hydration_mode' => 'HYDRATE_ARRAY'
        ));

        return $this->model->createForm($entity, $this->get('form.factory'), null, array('fields' => $fields));
    }

    /**
     * Obtains a list of users for lead owner edits
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOwnersAction ()
    {
        if (!$this->factory->getSecurity()->isGranted(
            array('lead:leads:create', 'lead:leads:editown', 'lead:leads:editother'),
            'MATCH_ONE'
        )
        ) {
            return $this->accessDenied();
        }

        $filter  = $this->request->query->get('filter', null);
        $limit   = $this->request->query->get('limit', null);
        $start   = $this->request->query->get('start', null);
        $users   = $this->model->getLookupResults('user', $filter, $limit, $start);
        $view    = $this->view($users, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(array('userList'));
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of custom fields
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getFieldsAction ()
    {
        if (!$this->factory->getSecurity()->isGranted(
            array('lead:leads:editown', 'lead:leads:editother'),
            'MATCH_ONE'
        )
        ) {
            return $this->accessDenied();
        }

        $fields = $this->factory->getModel('lead.field')->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true
                    )
                )
            )
        ));

        $view    = $this->view($fields, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(array('leadFieldList'));
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }


    /**
     * Obtains a list of notes on a specific lead
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getNotesAction ($id)
    {
        $entity = $this->model->getEntity($id);
        if ($entity !== null) {
            if (!$this->factory->getSecurity()->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getOwner())) {
                return $this->accessDenied();
            }

            $notes   = $this->factory->getModel('lead.note')->getEntities(array(
                'filter'     => array(
                    'force' => array(
                        array(
                            'column' => 'e.lead',
                            'expr'   => 'eq',
                            'value'  => $entity
                        )
                    )
                ),
                'orderBy'    => 'e.dateAdded',
                'orderByDir' => 'DESC'
            ));
            $view    = $this->view($notes, Codes::HTTP_OK);
            $context = SerializationContext::create()->setGroups(array('leadNoteList'));
            $view->setSerializationContext($context);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\Lead  &$entity
     * @param                                 $parameters
     * @param                                 $form
     * @param string                          $action
     */
    protected function preSaveEntity (&$entity, $form, $parameters, $action = 'edit')
    {
        //Since the request can be from 3rd party, check for an IP address if included
        if (isset($parameters['ipAddress'])) {
            $ip = $parameters['ipAddress'];
            unset($parameters['ipAddress']);

            $ipAddress = $this->factory->getIpAddress($ip);

            $entity->addIpAddress($ipAddress);
        }

        //set the custom field values

        //pull the data from the form in order to apply the form's formatting
        foreach ($form as $f) {
            $parameters[$f->getName()] = $f->getData();
        }

        $this->model->setFieldValues($entity, $parameters);
    }

    /**
     * Remove IpAddress as it'll be handled outsie the form
     *
     * @param $parameters
     * @param $entity
     * @param $action
     *
     * @return mixed|void
     */
    protected function prepareParametersForBinding ($parameters, $entity, $action)
    {
        unset($parameters['ipAddress']);

        return $parameters;
    }
}