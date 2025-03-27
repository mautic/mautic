<?php

namespace Mautic\PointBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Point>
 */
class PointApiController extends CommonApiController
{
    use LeadAccessTrait;

    protected LeadModel $leadModel;

    /**
     * @var PointModel|null
     */
    protected $model;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $leadModel = $modelFactory->getModel('lead');
        \assert($leadModel instanceof LeadModel);

        $pointModel = $modelFactory->getModel('point');
        \assert($pointModel instanceof PointModel);

        $this->model            = $pointModel;
        $this->leadModel        = $leadModel;
        $this->entityClass      = Point::class;
        $this->entityNameOne    = 'point';
        $this->entityNameMulti  = 'points';
        $this->serializerGroups = ['pointDetails', 'categoryList', 'publishDetails'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Return array of available point action types.
     */
    public function getPointActionTypesAction()
    {
        if (!$this->security->isGranted([$this->permissionBase.':view', $this->permissionBase.':viewown'])) {
            return $this->accessDenied();
        }

        $actionTypes = $this->model->getPointActions();
        $view        = $this->view(['pointActionTypes' => $actionTypes['list']]);

        return $this->handleView($view);
    }

    /**
     * Subtract points from a lead.
     *
     * @param int    $leadId
     * @param string $operator
     * @param int    $delta
     *
     * @return Response
     */
    public function adjustPointsAction(Request $request, IpLookupHelper $ipLookupHelper, $leadId, $operator, $delta)
    {
        $lead = $this->checkLeadAccess($leadId, 'edit');
        if ($lead instanceof Response) {
            return $lead;
        }

        try {
            $this->logApiPointChange($request, $ipLookupHelper, $lead, $delta, $operator);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->handleView($this->view(['success' => 1], Response::HTTP_OK));
    }

    /**
     * Log the lead points change.
     *
     * @param int $delta
     */
    protected function logApiPointChange(Request $request, IpLookupHelper $ipLookupHelper, $lead, $delta, $operator)
    {
        $trans      = $this->translator;
        $ip         = $ipLookupHelper->getIpAddress();
        $eventName  = InputHelper::clean($request->request->get('eventName', $trans->trans('mautic.lead.lead.submitaction.operator_'.$operator)));
        $actionName = InputHelper::clean($request->request->get('actionName', $trans->trans('mautic.lead.event.api')));

        $lead->adjustPoints($delta, $operator);
        $lead->addPointsChangeLogEntry('API', $eventName, $actionName, $delta, $ip);
        $this->leadModel->saveEntity($lead, false);
    }
}
