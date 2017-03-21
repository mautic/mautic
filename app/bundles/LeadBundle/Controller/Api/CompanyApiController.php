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
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class CompanyApiController.
 */
class CompanyApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait, LeadAccessTrait;

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model              = $this->getModel('lead.company');
        $this->entityClass        = Company::class;
        $this->entityNameOne      = 'company';
        $this->entityNameMulti    = 'companies';
        $this->serializerGroups[] = 'companyDetails';

        parent::initialize($event);
    }

    /**
     * If an existing company is matched, it'll be merged. Otherwise it'll be created.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newEntityAction()
    {
        // Check for an email to see if the lead already exists
        $parameters = $this->request->request->all();

        if (empty($parameters['force'])) {
            list($company, $companyEntities) = IdentifyCompanyHelper::findCompany($parameters, $this->getModel('lead.company'));

            if (count($companyEntities)) {
                return $this->editEntityAction($company['id']);
            }
        }

        return parent::newEntityAction();
    }

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
        $this->setCustomFieldValues($entity, $form, $parameters);
    }

    /**
     * Adds a contact to a company.
     *
     * @param int $companyId Company ID
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

        $contact = $this->checkLeadAccess($contactId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }

        $this->model->addLeadToCompany($company, $contact);

        return $this->handleView($view);
    }

    /**
     * Removes given contact from a company.
     *
     * @param int $companyId List ID
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
