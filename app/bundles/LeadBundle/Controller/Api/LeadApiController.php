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

    public function initialize(FilterControllerEvent $event)
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
     * Creates a new lead or edits if one is found with same email.  You should make a call to /api/leads/list/fields in order to get a list of custom fields that will be accepted. The key should be the alias of the custom field. You can also pass in a ipAddress parameter if the IP of the lead is different than that of the originating request.
     */
    public function newEntityAction()
    {
        // Check for an email to see if the lead already exists
        $parameters = $this->request->request->all();

        $uniqueLeadFields    = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueLeadFieldData = array();

        foreach ($parameters as $k => $v) {
            if (array_key_exists($k, $uniqueLeadFields) && !empty($v)) {
                $uniqueLeadFieldData[$k] = $v;
            }
        }

        if (count($uniqueLeadFieldData)) {
            if (count($uniqueLeadFieldData)) {
                $existingLeads = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->getLeadsByUniqueFields($uniqueLeadFieldData);

                if (!empty($existingLeads)) {
                    // Lead found so edit rather than create a new one

                    return parent::editEntityAction($existingLeads[0]->getId());
                }
            }
        }

        return parent::newEntityAction();
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     *
     * @return mixed|void
     */
    protected function createEntityForm($entity)
    {
        $fields = $this->factory->getModel('lead.field')->getEntities(
            array(
                'force'          => array(
                    array(
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true
                    )
                ),
                'hydration_mode' => 'HYDRATE_ARRAY'
            )
        );

        return $this->model->createForm($entity, $this->get('form.factory'), null, array('fields' => $fields));
    }

    /**
     * Obtains a list of users for lead owner edits
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOwnersAction()
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
    public function getFieldsAction()
    {
        if (!$this->factory->getSecurity()->isGranted(array('lead:leads:editown', 'lead:leads:editother'), 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $fields = $this->factory->getModel('lead.field')->getEntities(
            array(
                'filter' => array(
                    'force' => array(
                        array(
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true
                        )
                    )
                )
            )
        );

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
    public function getNotesAction($id)
    {
        $entity = $this->model->getEntity($id);
        if ($entity !== null) {
            if (!$this->factory->getSecurity()->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getOwner())) {
                return $this->accessDenied();
            }

            $results = $this->factory->getModel('lead.note')->getEntities(
                array(
                    'start'      => $this->request->query->get('start', 0),
                    'limit'      => $this->request->query->get('limit', $this->factory->getParameter('default_pagelimit')),
                    'filter'     => array(
                        'string' => $this->request->query->get('search', ''),
                        'force'  => array(
                            array(
                                'column' => 'n.lead',
                                'expr'   => 'eq',
                                'value'  => $entity
                            )
                        )
                    ),
                    'orderBy'    => $this->request->query->get('orderBy', 'n.dateAdded'),
                    'orderByDir' => $this->request->query->get('orderByDir', 'DESC')
                )
            );

            list($notes, $count) = $this->prepareEntitiesForView($results);

            $view = $this->view(
                array(
                    'total' => $count,
                    'notes' => $notes
                ),
                Codes::HTTP_OK
            );

            $context = SerializationContext::create()->setGroups(array('leadNoteDetails'));
            $view->setSerializationContext($context);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Obtains a list of lead lists the lead is in
     *
     * @param $id
     */
    public function getListsAction($id)
    {
        $entity = $this->model->getEntity($id);
        if ($entity !== null) {
            if (!$this->factory->getSecurity()->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getOwner())) {
                return $this->accessDenied();
            }

            $lists = $this->model->getLists($entity, true, true);

            $view = $this->view(
                array(
                    'total' => count($lists),
                    'lists' => $lists
                ),
                Codes::HTTP_OK
            );

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Obtains a list of campaigns the lead is part of
     *
     * @param $id
     */
    public function getCampaignsAction($id)
    {
        $entity = $this->model->getEntity($id);
        if ($entity !== null) {
            if (!$this->factory->getSecurity()->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getOwner())) {
                return $this->accessDenied();
            }

            /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
            $campaignModel = $this->factory->getModel('campaign');
            $campaigns = $campaignModel->getLeadCampaigns($entity, true);

            foreach ($campaigns as &$c) {
                unset($c['lists']);
            }

            $view = $this->view(
                array(
                    'total'     => count($campaigns),
                    'campaigns' => $campaigns
                ),
                Codes::HTTP_OK
            );

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
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
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
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        unset($parameters['ipAddress']);

        return $parameters;
    }

    /**
     * Flatten fields into an 'all' key for dev convenience
     *
     * @param $entity
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $fields        = $entity->getFields();
        $all           = $this->model->flattenFields($fields);
        $fields['all'] = $all;
        $entity->setFields($fields);
    }
}
