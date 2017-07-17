<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class PointApiController.
 */
class PointApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('point');
        $this->leadModel        = $this->getModel('lead');
        $this->entityClass      = 'Mautic\PointBundle\Entity\Point';
        $this->entityNameOne    = 'point';
        $this->entityNameMulti  = 'points';
        $this->serializerGroups = ['pointDetails', 'categoryList', 'publishDetails'];

        parent::initialize($event);
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function adjustPointsAction($leadId, $operator, $delta)
    {
        $lead = $this->checkLeadAccess($leadId, 'edit');
        if ($lead instanceof Response) {
            return $lead;
        }

        try {
            $this->logApiPointChange($lead, $delta, $operator);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Codes::HTTP_BAD_REQUEST);
        }

        return $this->handleView($this->view(['success' => 1], Codes::HTTP_OK));
    }

    /**
     * Log the lead points change.
     *
     * @param int $leadId
     * @param int $delta
     */
    protected function logApiPointChange($lead, $delta, $operator)
    {
        $trans      = $this->get('translator');
        $ip         = $this->get('mautic.helper.ip_lookup')->getIpAddress();
        $eventName  = InputHelper::clean($this->request->request->get('eventName', $trans->trans('mautic.lead.lead.submitaction.operator_'.$operator)));
        $actionName = InputHelper::clean($this->request->request->get('actionName', $trans->trans('mautic.lead.event.api')));

        $lead->adjustPoints($delta, $operator);
        $lead->addPointsChangeLogEntry('API', $eventName, $actionName, $delta, $ip);
        $this->leadModel->saveEntity($lead, false);
    }
}
