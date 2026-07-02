<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanyModel;
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
final class CompanySegmentApiController extends CommonApiController
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeCompanyAction(CompanyModel $companyModel, int $id, int $companyId): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

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

        $view = $this->view([], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addCompanyAction(CompanyModel $companyModel, int $id, int $companyId): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

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

        $view = $this->view([], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Adds companies to a company segment.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addCompaniesAction(CompanyModel $companyModel, Request $request, int $id): Response
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

        $view = $this->view(['details' => $responseDetail], Response::HTTP_OK);

        return $this->handleView($view);
    }
}
