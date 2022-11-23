<?php

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Model\DeviceModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<LeadDevice>
 */
class DeviceApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(ControllerEvent $event)
    {
        $leadDeviceModel = $this->getModel('lead.device');

        if (!$leadDeviceModel instanceof DeviceModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model           = $leadDeviceModel;
        $this->entityClass     = LeadDevice::class;
        $this->entityNameOne   = 'device';
        $this->entityNameMulti = 'devices';

        parent::initialize($event);
    }

    /**
     * {@inheritdoc}
     *
     * @param LeadDevice &$entity
     * @param            $parameters
     * @param            $form
     * @param string     $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $lead = null;
        if (!empty($parameters['lead'])) {
            $lead = $parameters['lead'];
        } elseif (!empty($parameters['contact'])) {
            $lead = $parameters['contact'];
        }
        if ($lead) {
            $lead = $this->checkLeadAccess($lead, $action);

            if ($lead instanceof Response) {
                return $lead;
            }

            $entity->setLead($lead);
            unset($parameters['lead'], $parameters['contact']);
        } elseif ('new' === $action) {
            return $this->returnError('contact ID is mandatory', Response::HTTP_BAD_REQUEST);
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
