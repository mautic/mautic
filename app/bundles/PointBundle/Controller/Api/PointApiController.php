<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller\Api;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Event\ApiEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PointApiController
 */
class PointApiController extends CommonApiController
{

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('point');
        $this->entityClass      = 'Mautic\PointBundle\Entity\Point';
        $this->entityNameOne    = 'point';
        $this->entityNameMulti  = 'points';
        $this->permissionBase   = 'point:points';
        $this->serializerGroups = array('pointDetails', 'categoryList', 'publishDetails');
    }

    /**
     * @param unknown $id
     * @param unknown $leadId
     *
     * @return
     */
    public function applyRuleAction($id, $leadId)
    {
        if (empty($id) || empty($leadId)) {
            return new JsonResponse(array(
                "message" => "A points rule ID and contact ID are required",
                "success" => false
            ));
        }

        $lead = $this->factory->getModel('lead')->getEntity($leadId);

        $event = new ApiEvent($lead, $id);

        $this->factory->getDispatcher()->dispatch(ApiEvents::API_CALL_APPLYRULE, $event);

        return new JsonResponse(array(
            "success" => true
        ));
    }
}
