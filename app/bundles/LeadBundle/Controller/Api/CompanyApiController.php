<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class CompanyApiController.
 */
class CompanyApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->getModel('lead.company');
        $this->entityClass     = Company::class;
        $this->entityNameOne   = 'company';
        $this->entityNameMulti = 'companies';
        $this->permissionBase  = 'lead:leads';
    }

    // @todo - company merging is not ready yet
    // public function newEntityAction()
    // {
    //     // Check for the unitque field values to see if the company already exists
    //     $parameters = $this->request->request->all();

    //     $uniqueLeadFields    = $this->getModel('lead.field')->getUniqueIdentiferFields(['object' => 'company']);
    //     $uniqueLeadFieldData = [];

    //     foreach ($parameters as $k => $v) {
    //         if (array_key_exists($k, $uniqueLeadFields) && !empty($v)) {
    //             $uniqueLeadFieldData[$k] = $v;
    //         }
    //     }

    //     if (count($uniqueLeadFieldData)) {
    //         if (count($uniqueLeadFieldData)) {
    //             $existingLeads = $this->get('doctrine.orm.entity_manager')->getRepository('MauticLeadBundle:Company')->getLeadsByUniqueFields($uniqueLeadFieldData);

    //             if (!empty($existingLeads)) {
    //                 // Lead found so edit rather than create a new one

    //                 return parent::editEntityAction($existingLeads[0]->getId());
    //             }
    //         }
    //     }

    //     return parent::newEntityAction();
    // }

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
        //set the custom field values

        //pull the data from the form in order to apply the form's formatting
        foreach ($form as $f) {
            $parameters[$f->getName()] = $f->getData();
        }

        $this->model->setFieldValues($entity, $parameters, true);
    }

    /**
     * @return array
     */
    protected function getEntityFormOptions()
    {
        $fields = $this->getModel('lead.field')->getEntities(
            [
                'force' => [
                    [
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                    [
                        'column' => 'f.object',
                        'expr'   => 'eq',
                        'value'  => 'company',
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        return ['fields' => $fields, 'update_select' => false];
    }

    /**
     * Flatten fields into an 'all' key for dev convenience.
     *
     * @param        $entity
     * @param string $action
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        if ($entity instanceof Company) {
            $fields        = $entity->getFields();
            $all           = $this->getModel('lead')->flattenFields($fields);
            $fields['all'] = $all;
            $entity->setFields($fields);
        }
    }

    /*
     * Adds a contact to a company.
     *
     * @param int $companyId     Company ID
     * @param int $contactId Contact ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addContactAction($companyId, $contactId)
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Codes::HTTP_OK);

        if ($company === null) {
            return $this->notFound();
        }

        $contactModel = $this->getModel('lead');
        $contact      = $contactModel->getEntity($contactId);

        // Does the contact exist and the user has permission to edit
        if ($contact === null) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        $this->model->addLeadToCompany($company, $contact);

        return $this->handleView($view);
    }

    /*
     * Removes given contact from a company.
     *
     * @param int $companyId     List ID
     * @param int $contactId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeContactAction($companyId, $contactId)
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Codes::HTTP_OK);

        if ($company === null) {
            return $this->notFound();
        }

        $contactModel = $this->getModel('lead');
        $contact      = $contactModel->getEntity($contactId);

        // Does the contact exist and the user has permission to edit
        if ($contact === null) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        $this->model->removeLeadFromCompany($company, $contact);

        return $this->handleView($view);
    }
}
