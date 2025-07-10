<?php

namespace Mautic\CampaignBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\ApiBundle\Serializer\Exclusion\FieldExclusionStrategy;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Event>
 */
class EventApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $campaignEventModel = $modelFactory->getModel('campaign.event');
        \assert($campaignEventModel instanceof EventModel);

        $this->model                    = $campaignEventModel;
        $this->entityClass              = Event::class;
        $this->entityNameOne            = 'event';
        $this->entityNameMulti          = 'events';
        $this->serializerGroups         = ['campaignEventStandaloneDetails', 'campaignList'];
        $this->parentChildrenLevelDepth = 1;

        // Don't include campaign in children/parent arrays
        $this->addExclusionStrategy(new FieldExclusionStrategy(['campaign'], 1));

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @param Event  $entity
     * @param string $action
     *
     * @return bool|mixed
     */
    protected function checkEntityAccess($entity, $action = 'view')
    {
        // Use the campaign for permission checks
        return parent::checkEntityAccess($entity->getCampaign(), $action);
    }
}
