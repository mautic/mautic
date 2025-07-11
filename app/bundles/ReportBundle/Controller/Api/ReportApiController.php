<?php

namespace Mautic\ReportBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Exception\PermissionException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Report>
 */
class ReportApiController extends CommonApiController
{
    /**
     * @var ReportModel|null
     */
    protected $model;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper, protected UserHelper $userHelper)
    {
        $reportModel = $modelFactory->getModel('report');
        \assert($reportModel instanceof ReportModel);

        $this->model            = $reportModel;
        $this->entityClass      = Report::class;
        $this->entityNameOne    = 'report';
        $this->entityNameMulti  = 'reports';
        $this->serializerGroups = ['reportList', 'reportDetails'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Obtains a compiled report.
     *
     * @param int $id Report ID
     */
    public function getEntityAction(Request $request, $id): Response
    {
        try {
            if (!$this->security->isGranted($this->permissionBase.':view')) {
                return $this->accessDenied();
            }
        } catch (PermissionException $e) {
            return $this->accessDenied($e->getMessage());
        }

        $entity        = $this->model->getEntity($id);

        if (!$entity instanceof $this->entityClass) {
            return $this->notFound();
        }

        if (
            $this->security->checkPermissionExists($this->permissionBase.':viewother')
            && !$this->security->isGranted($this->permissionBase.':viewother')
            && $entity->getCreatedBy() !== $this->userHelper->getUser()->getId()
        ) {
            return $this->accessDenied();
        }

        $reportData = $this->model->getReportData($entity, $this->formFactory, $this->getOptionsFromRequest($request));

        // Unset keys that we don't need to send back
        foreach (['graphs', 'contentTemplate', 'columns'] as $key) {
            unset($reportData[$key]);
        }

        // Include report metadata
        $reportData[$this->entityNameOne] = $entity;

        return $this->handleView(
            $this->view($reportData, Response::HTTP_OK)
        );
    }

    public function getReportAction(Request $request, int $id): Response
    {
        return $this->getEntityAction($request, $id);
    }

    /**
     * This method is careful to add new options from the request to keep BC.
     * It originally loaded all rows without any filter or pagination applied.
     */
    private function getOptionsFromRequest(Request $request): array
    {
        $options = ['paginate'=> false, 'ignoreGraphData' => true];

        if ($request->query->has('dateFrom')) {
            $options['dateFrom'] = new \DateTimeImmutable($request->query->get('dateFrom'), new \DateTimeZone('UTC'));
        }

        if ($request->query->has('dateTo')) {
            $options['dateTo']   = new \DateTimeImmutable($request->query->get('dateTo'), new \DateTimeZone('UTC'));
        }

        if ($request->query->has('page')) {
            $options['page']     = $request->query->getInt('page');
            $options['paginate'] = true;
        }

        if ($request->query->has('limit')) {
            $options['limit']    = $request->query->getInt('limit');
            $options['paginate'] = true;
        }

        return $options;
    }
}
