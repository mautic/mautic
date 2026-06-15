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
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Model\FieldGroupModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<FieldGroup>
 */
class FieldGroupApiController extends CommonApiController
{
    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $fieldGroupModel = $modelFactory->getModel('lead.field_group');
        \assert($fieldGroupModel instanceof FieldGroupModel);

        $this->model            = $fieldGroupModel;
        $this->entityClass      = FieldGroup::class;
        $this->entityNameOne    = 'fieldGroup';
        $this->entityNameMulti  = 'fieldGroups';
        $this->serializerGroups = ['fieldGroupDetails', 'fieldGroupList'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * The "Group order" dropdown is GUI-only; the API sets the order property directly.
     *
     * @return array<string, mixed>
     */
    protected function getEntityFormOptions(): array
    {
        return ['include_order_field' => false];
    }
}
