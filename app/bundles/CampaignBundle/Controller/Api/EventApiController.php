<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Serializer\Exclusion\FieldExclusionStrategy;
use Mautic\CampaignBundle\Entity\Event;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class EventApiController.
 */
class EventApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model                    = $this->getModel('campaign.event');
        $this->entityClass              = 'Mautic\CampaignBundle\Entity\Event';
        $this->entityNameOne            = 'event';
        $this->entityNameMulti          = 'events';
        $this->permissionBase           = 'campaign:campaigns';
        $this->serializerGroups         = ['campaignEventStandaloneDetails', 'campaignList'];
        $this->parentChildrenLevelDepth = 1;

        // Don't include campaign in children/parent arrays
        $this->addExclusionStrategy(new FieldExclusionStrategy(['campaign'], 1));
    }

    public function getContactEventsAction($contactId, $campaignId = null)
    {
        //campaigns/{campaignId}/events/contact/{contactId}
        //campaigns/events/contact/{contactId}
    }

    public function editContactEventAction($id, $contactId)
    {
        //campaigns/events/contact/{contactId}/event/{eventId}/edit
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
        return $this->checkEntityAccess($entity->getCampaign(), $action);
    }
}
