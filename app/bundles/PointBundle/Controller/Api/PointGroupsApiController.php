<?php

namespace Mautic\PointBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Model\PointGroupModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Group>
 */
class PointGroupsApiController extends CommonApiController
{
    /**
     * @var PointGroupModel
     */
    protected $model;

    private LeadModel $leadModel;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper, MauticFactory $factory, PointGroupModel $pointGroupModel, LeadModel $leadModel)
    {
        $this->model            = $pointGroupModel;
        $this->leadModel        = $leadModel;
        $this->entityClass      = Group::class;
        $this->entityNameOne    = 'group';
        $this->entityNameMulti  = 'groups';
        $this->serializerGroups = ['pointGroupDetails', 'pointGroupList', 'publishDetails'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $factory);
    }

    public function getLeadGroupPointsAction(int $leadId): Response
    {
        $lead = $this->leadModel->getEntity($leadId);

        if (null === $lead) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($lead)) {
            return $this->accessDenied();
        }

        $groupScores = $lead->getGroupScores();
        $view        = $this->view(
            [
                'total'       => count($groupScores),
                'groupScores' => $groupScores,
            ],
            Response::HTTP_OK
        );

        $context = $view->getContext()->setGroups(['groupContactScoreDetails', 'pointGroupDetails']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    public function adjustGroupPointsAction(int $leadId, int $groupId, string $operator, int $value)
    {
        $lead = $this->leadModel->getEntity($leadId);

        if (null === $lead) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($lead)) {
            return $this->accessDenied();
        }

        $pointGroup = $this->model->getEntity($groupId);
        if (null === $pointGroup) {
            return $this->notFound();
        }

        if (!PointGroupModel::isAllowedPointOperation($operator)) {
            return $this->badRequest();
        }

        $this->model->adjustPoints($lead, $pointGroup, $value, $operator);

        return $this->handleView($this->view(['success' => 1], Response::HTTP_OK));
    }
}
