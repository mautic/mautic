<?php

namespace Mautic\CampaignBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Serializer\Exclusion\FieldExclusionStrategy;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Event>
 */
class EventApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(ControllerEvent $event)
    {
        $campaignEventModel = $this->getModel('campaign.event');

        if (!$campaignEventModel instanceof EventModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model                    = $campaignEventModel;
        $this->entityClass              = Event::class;
        $this->entityNameOne            = 'event';
        $this->entityNameMulti          = 'events';
        $this->serializerGroups         = ['campaignEventStandaloneDetails', 'campaignList'];
        $this->parentChildrenLevelDepth = 1;

        // Don't include campaign in children/parent arrays
        $this->addExclusionStrategy(new FieldExclusionStrategy(['campaign'], 1));

        parent::initialize($event);
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
