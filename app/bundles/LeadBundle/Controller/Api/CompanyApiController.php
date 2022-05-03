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

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CompanyApiController.
 */
class CompanyApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait;
    use LeadAccessTrait;

    public function initialize(ControllerArgumentsEvent $event)
    {
        $this->model              = $this->getModel('lead.company');
        $this->entityClass        = Company::class;
        $this->entityNameOne      = 'company';
        $this->entityNameMulti    = 'companies';
        $this->serializerGroups[] = 'companyDetails';
        $this->setCleaningRules('company');
        parent::initialize($event);
    }

    /**
     * If an existing company is matched, it'll be merged. Otherwise it'll be created.
     *
     * @return Response
     */
    public function newEntityAction()
    {
        // Check for an email to see if the lead already exists
        $parameters = $this->request->request->all();

        if (empty($parameters['force'])) {
            [$company, $companyEntities] = IdentifyCompanyHelper::findCompany($parameters, $this->getModel('lead.company'));

            if (count($companyEntities)) {
                return $this->editEntityAction($company['id']);
            }
        }

        return parent::newEntityAction();
    }

    /**
     * {@inheritdoc}
     *
     * @param Company                              $entity
     * @param ?array<int|string|array<int|string>> $parameters
     * @param Form                                 $form
     * @param string                               $action
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
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function addContactAction($companyId, $contactId)
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Response::HTTP_OK);

        if (null === $company) {
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
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function removeContactAction($companyId, $contactId)
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Response::HTTP_OK);

        if (null === $company) {
            return $this->notFound();
        }

        $contactModel = $this->getModel('lead');
        $contact      = $contactModel->getEntity($contactId);

        // Does the contact exist and the user has permission to edit
        if (null === $contact) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        $this->model->removeLeadFromCompany($company, $contact);

        return $this->handleView($view);
    }
}
