<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<CompanySegment>
 */
class CompanySegmentApiController extends CommonApiController
{
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
        private CompanySegmentModel $companySegmentModel,
    ) {
        $this->model             = $this->companySegmentModel;
        $this->entityClass       = CompanySegment::class;
        $this->entityNameOne     = 'companysegment';
        $this->entityNameMulti   = 'companysegments';
        $this->permissionBase    = 'lead:lists';
        parent::__construct(
            $security,
            $translator,
            $entityResultHelper,
            $router,
            $formFactory,
            $appVersion,
            $requestStack,
            $doctrine,
            $modelFactory,
            $dispatcher,
            $coreParametersHelper
        );
    }

    /**
     * @param int $id Entity ID
     *
     * @return Response
     */
    public function deleteEntityAction($id)
    {
        $model = $this->getModel(CompanySegmentModel::class);
        \assert($model instanceof CompanySegmentModel);
        $entity = $model->getEntity($id);
        if (null !== $entity) {
            $access = $this->checkEntityAccess($entity, 'delete');

            if (is_bool($access) && !$access) {
                return $this->accessDenied();
            }

            return parent::deleteEntityAction($id);
        }

        return $this->notFound();
    }

    /**
     * @return array<mixed>|Response
     */
    public function deleteEntitiesAction(Request $request)
    {
        $parameters = $request->query->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        /** @var array<int, array<int|string>> $errors */
        $errors            = [];
        /** @var array<int, CompanySegment|null> $entities */
        $entities          = $this->getBatchEntities($parameters, $errors, true);

        $this->inBatchMode = true;

        // Generate the view before deleting so that the IDs are still populated before Doctrine removes them
        $payload = [$this->entityNameMulti => $entities];
        $view    = $this->view($payload, Response::HTTP_OK);
        $this->setSerializationContext($view);
        $response = $this->handleView($view);

        foreach ($entities as $key => $entity) {
            if (!($entity instanceof CompanySegment) || null === $entity->getId() || 0 === $entity->getId()) {
                $entityError = $entity instanceof CompanySegment ? $entity : null;
                /** @var array<int, array<int|string>> $errors */
                $this->setBatchError($key, 'mautic.core.error.notfound', Response::HTTP_NOT_FOUND, $errors, $entities, $entityError);
                continue;
            }

            if (false === $this->checkEntityAccess($entity, 'delete')) {
                /** @var array<int, array<int|string>> $errors */
                $this->setBatchError($key, 'mautic.core.error.accessdenied', Response::HTTP_FORBIDDEN, $errors, $entities, $entity);
                continue;
            }

            assert($this->model instanceof CompanySegmentModel);

            try {
                $this->model->deleteEntity($entity);
            } catch (DeleteEntityDependencyException $e) {
                $errorMessage = $this->translator->trans('mautic.company_segments.api.error.delete_has_dependencies', ['%segments%' => $e->getMessage()]);
                /** @var array<int, array<int|string>> $errors */
                $this->setBatchError($key, $errorMessage, Response::HTTP_CONFLICT, $errors, $entities, $entity);
                continue;
            }
            $this->doctrine->getManager()->detach($entity);
        }

        if ([] !== $errors && $response instanceof Response) {
            $responseContent = '';
            if (is_string($response->getContent())) {
                $responseContent = $response->getContent();
            }

            $content           = json_decode($responseContent, true);
            if (null === $content || !is_array($content)) {
                $content = [];
            }

            $content['errors'] = $errors;
            $text              = json_encode($content);
            if (is_string($text)) {
                $response->setContent($text);
            }
        }

        return $response;
    }

    /**
     * @param int $id        Company Segment ID
     * @param int $companyId Company ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeCompanyAction($id, $companyId)
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        $companyModel = $this->getModel('lead.company');
        \assert($companyModel instanceof \Mautic\LeadBundle\Model\CompanyModel);
        $company = $companyModel->getEntity($companyId);

        if (null === $company) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $company->getPermissionUser())) {
            return $this->accessDenied();
        }

        \assert($this->model instanceof CompanySegmentModel);

        // Does the user have access to the company segment
        $companySegments = $this->model->getCompanySegments();
        if (!isset($companySegments[$id])) {
            return $this->accessDenied();
        }

        $this->model->removeCompany($company, [$entity], true);

        $view = $this->view(['success' => 1], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @param int $id        Company Segment ID
     * @param int $companyId Company ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addCompanyAction($id, $companyId)
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        $companyModel = $this->getModel('lead.company');
        \assert($companyModel instanceof \Mautic\LeadBundle\Model\CompanyModel);
        $company = $companyModel->getEntity($companyId);

        if (null === $company) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $company->getPermissionUser())) {
            return $this->accessDenied();
        }

        \assert($this->model instanceof CompanySegmentModel);

        // Does the user have access to the company segment
        $companySegments = $this->model->getCompanySegments();
        if (!isset($companySegments[$id])) {
            return $this->accessDenied();
        }

        $this->model->addCompany($company, [$entity], true);

        $view = $this->view(['success' => 1], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Adds companies to a company segment.
     *
     * @param int $id Company Segment ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addCompaniesAction(Request $request, $id)
    {
        $companyIds = $request->request->all()['ids'] ?? null;
        if (null === $companyIds) {
            return $this->returnError('mautic.core.error.badrequest', Response::HTTP_BAD_REQUEST);
        }

        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        \assert($this->model instanceof CompanySegmentModel);

        // Does the user have access to the company segment
        $companySegments = $this->model->getCompanySegments();
        if (!isset($companySegments[$id])) {
            return $this->accessDenied();
        }

        $companyModel = $this->getModel('lead.company');
        \assert($companyModel instanceof \Mautic\LeadBundle\Model\CompanyModel);

        $responseDetail = [];
        foreach ($companyIds as $companyId) {
            $company = $companyModel->getEntity($companyId);

            if (null === $company) {
                $responseDetail[$companyId] = ['success' => false];
            } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $company->getPermissionUser())) {
                $responseDetail[$companyId] = ['success' => false];
            } else {
                $this->model->addCompany($company, [$entity], true);
                $responseDetail[$company->getId()] = ['success' => true];
            }
        }

        $view = $this->view(['success' => 1, 'details' => $responseDetail], Response::HTTP_OK);

        return $this->handleView($view);
    }
}
