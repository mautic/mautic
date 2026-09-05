<?php

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Company>
 */
final class CompanyApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait;
    use LeadAccessTrait;

    /**
     * @var CompanyModel|null
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        private readonly CompanyModel $companyModel,
        private readonly LeadModel $leadModel,
        private readonly IdentifyCompanyHelper $identifyCompanyHelper,
    ) {
        $this->model              = $companyModel;
        $this->entityClass        = Company::class;
        $this->entityNameOne      = 'company';
        $this->entityNameMulti    = 'companies';
        $this->serializerGroups[] = 'companyDetails';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    public function getNewEntity(array $params)
    {
        [$company, $companyEntities] = $this->identifyCompanyHelper->findCompany($params);
        if (count($companyEntities)) {
            return $this->model->getEntity($company['id']);
        }

        return $this->model->getEntity();
    }

    /**
     * @param Company              $entity
     * @param FormInterface<mixed> $form
     * @param array<mixed>         $parameters
     * @param string               $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit'): void
    {
        $this->setCustomFieldValues($entity, $form, $parameters);
    }

    /**
     * Adds a contact to a company.
     *
     * @param int $companyId Company ID
     * @param int $contactId Contact ID
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    #[Route(
        '/api/companies/{companyId}/contact/{contactId}/add',
        name: 'mautic_api_companyaddcontact',
        requirements: ['companyId' => '\d+', 'contactId' => '\d+'],
        defaults: ['_format' => 'json'],
        methods: ['POST']
    )]
    public function addContactAction($companyId, $contactId): Response
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    #[Route(
        '/api/companies/{companyId}/contact/{contactId}/remove',
        name: 'mautic_api_companyremovecontact',
        requirements: ['companyId' => '\d+', 'contactId' => '\d+'],
        defaults: ['_format' => 'json'],
        methods: ['POST']
    )]
    public function removeContactAction($companyId, $contactId): Response
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Response::HTTP_OK);

        if (null === $company) {
            return $this->notFound();
        }

        $contact      = $this->leadModel->getEntity($contactId);

        // Does the contact exist and the user has permission to edit
        if (null === $contact) {
            return $this->notFound();
        }
        if (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        $this->model->removeLeadFromCompany($company, $contact);

        return $this->handleView($view);
    }
}
