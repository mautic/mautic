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
use Mautic\LeadBundle\Model\BatchCompanyContactAssignmentModel;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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
        private CompanyModel $companyModel,
        private LeadModel $leadModel,
        private BatchCompanyContactAssignmentModel $batchCompanyContactAssignmentModel,
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
        [$company, $companyEntities] = IdentifyCompanyHelper::findCompany($params, $this->companyModel);
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

        $addedCompanyIds = $this->model->addLeadToCompany($company, $contact);

        try {
            $this->batchCompanyContactAssignmentModel->logContactCompanyAssignments(
                $contact,
                $addedCompanyIds,
                [$company->getId() => $company],
            );
        } catch (\Throwable) {
            // Assignment succeeded; logging failure must not change the API outcome.
        }

        return $this->handleView($view);
    }

    /**
     * Assigns multiple contacts to multiple companies in one request.
     */
    public function batchAddContactsAction(Request $request): Response
    {
        if (!$this->security->isGranted('lead:leads:editown') && !$this->security->isGranted('lead:leads:editother')) {
            return $this->accessDenied();
        }

        $parameters   = $this->getBatchAddContactsParameters($request);
        $assignments = $parameters['assignments'] ?? null;

        if (!is_array($assignments) || [] === $assignments) {
            return $this->returnError('"assignments" parameter is required and must be a non-empty array', Response::HTTP_BAD_REQUEST);
        }

        foreach ($assignments as $entry) {
            if (!is_array($entry)) {
                return $this->returnError('Assignments entries must be a non-empty array', Response::HTTP_BAD_REQUEST);
            }
        }

        $valid = $this->validateBatchPayload($assignments);
        if ($valid instanceof Response) {
            return $valid;
        }

        $payload = $this->batchCompanyContactAssignmentModel->process($assignments);

        return $this->handleView($this->view($payload, Response::HTTP_OK));
    }

    /**
     * Prefer the raw JSON body so Content-Type: application/json works without relying on
     * request bag population; fall back to the request parameter bag for form posts.
     *
     * @return array<string, mixed>
     */
    private function getBatchAddContactsParameters(Request $request): array
    {
        $content = $request->getContent();
        if ('' !== $content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->request->all();
    }

    /**
     * Removes given contact from a company.
     *
     * @param int $companyId List ID
     * @param int $contactId Lead ID
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
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
