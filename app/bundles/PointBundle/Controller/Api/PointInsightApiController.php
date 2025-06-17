<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PointBundle\Entity\PointInsight;
use Mautic\PointBundle\Model\InsightModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<PointInsight>
 */
class PointInsightApiController extends CommonApiController
{
    /**
     * @var InsightModel
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        private ?RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $insightModel = $modelFactory->getModel('point.insight');
        \assert($insightModel instanceof InsightModel);

        $this->model            = $insightModel;
        $this->entityClass      = PointInsight::class;
        $this->entityNameOne    = 'pointInsight';
        $this->entityNameMulti  = 'pointInsights';
        $this->serializerGroups = ['pointInsightDetails', 'categoryList'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Return array of available point insight types and actions.
     */
    public function getPointInsightTypesAction(): Response
    {
        if (!$this->security->isGranted([$this->permissionBase.':view', $this->permissionBase.':viewown'])) {
            return $this->accessDenied();
        }

        $insightTypes = [
            'compare_point_groups' => $this->translator->trans('mautic.point.insight.type.compare_point_groups'),
        ];

        $insightActions = [
            'set_custom_field' => $this->translator->trans('mautic.point.insight.action.set_custom_field'),
        ];

        $view = $this->view([
            'insightTypes' => $insightTypes,
            'insightActions' => $insightActions,
        ]);

        return $this->handleView($view);
    }
}