<?php

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Company>
 */
class CompanyApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait;
    use LeadAccessTrait;

    /**
     * @var CompanyModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $companyModel = $this->getModel('lead.company');
        \assert($companyModel instanceof CompanyModel);

        $this->model              = $companyModel;
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newEntityAction()
    {
        // Check for an email to see if the lead already exists
        $parameters = $this->request->request->all();

        if (empty($parameters['force'])) {
            $leadCompanyModel = $this->getModel('lead.company');
            \assert($leadCompanyModel instanceof CompanyModel);
            list($company, $companyEntities) = IdentifyCompanyHelper::findCompany($parameters, $leadCompanyModel);

            if (count($companyEntities)) {
                return $this->editEntityAction($company['id']);
            }
        }

        return parent::newEntityAction();
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead   &$entity
     * @param        $parameters
     * @param        $form
     * @param string $action
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
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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
