<?php

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Model\DeviceModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<LeadDevice>
 */
class DeviceApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $leadDeviceModel = $modelFactory->getModel('lead.device');
        \assert($leadDeviceModel instanceof DeviceModel);

        $this->model           = $leadDeviceModel;
        $this->entityClass     = LeadDevice::class;
        $this->entityNameOne   = 'device';
        $this->entityNameMulti = 'devices';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @param LeadDevice &$entity
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

    protected function checkEntityAccess($entity, $action = 'view')
    {
        return parent::checkEntityAccess($entity->getLead(), $action);
    }
}
