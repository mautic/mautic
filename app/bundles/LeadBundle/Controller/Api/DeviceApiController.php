<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\LeadDevice;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class DeviceApiController.
 */
class DeviceApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->getModel('lead.device');
        $this->entityClass     = LeadDevice::class;
        $this->entityNameOne   = 'device';
        $this->entityNameMulti = 'devices';
        $this->permissionBase  = 'lead:leads';
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\LeadDevice &$entity
     * @param                                      $parameters
     * @param                                      $form
     * @param string                               $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (!empty($parameters['lead'])) {
            $lead = $this->checkLeadAccess($parameters['lead'], 'view');

            if ($lead instanceof Response) {
                return $lead;
            }

            $entity->setLead($lead);
            unset($parameters['lead']);
        } elseif ($action === 'new') {
            return $this->returnError('lead ID is mandatory', Codes::HTTP_BAD_REQUEST);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkEntityAccess($entity, $action = 'view')
    {
        return parent::checkEntityAccess($entity->getLead(), $action);
    }
}
