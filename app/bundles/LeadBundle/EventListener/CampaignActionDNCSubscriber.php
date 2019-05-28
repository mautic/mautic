<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Form\Type\CampaignDNCActionType;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionDNCSubscriber implements EventSubscriberInterface
{
    /**
     * @var DoNotContact
     */
    private $doNotContact;

    /**
     * CampaignActionDNCSubscriber constructor.
     *
     * @param DoNotContact $doNotContact
     */
    public function __construct(DoNotContact $doNotContact)
    {
        $this->doNotContact = $doNotContact;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['configureAction', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT    => ['addDoNotContact', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT => ['removeDoNotContact', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function configureAction(CampaignBuilderEvent $event)
    {
        $event->addAction(
            'lead.adddnc',
            [
                'label'                  => 'mautic.lead.lead.events.add_donotcontact',
                'description'            => 'mautic.lead.lead.events.add_donotcontact_desc',
                'batchEventName'         => LeadEvents::ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT,
                'formType'               => CampaignDNCActionType::class,
            ]
        );

        $event->addAction(
            'lead.removednc',
            [
                'label'                  => 'mautic.lead.lead.events.remove_donotcontact',
                'description'            => 'mautic.lead.lead.events.remove_donotcontact_desc',
                'batchEventName'         => LeadEvents::ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT,
                'formType'               => CampaignDNCActionType::class,
            ]
        );
    }

    /**
     * @param PendingEvent $event
     */
    public function addDoNotContact(PendingEvent $event)
    {
        $contactIds = $event->getContactIds();

        /*
                $this->removedContactTracker->addRemovedContacts(
                    $event->getEvent()->getCampaign()->getId(),
                    $contactIds
                );
        
                $this->leadModel->deleteEntities($contactIds);*/

        $event->passAll();
    }

    /**
     * @param PendingEvent $event
     */
    public function removeDoNotContact(PendingEvent $event)
    {
        $contactIds = $event->getContactIds();

        /*
                $this->removedContactTracker->addRemovedContacts(
                    $event->getEvent()->getCampaign()->getId(),
                    $contactIds
                );

                $this->leadModel->deleteEntities($contactIds);*/

        $event->passAll();
    }
}
