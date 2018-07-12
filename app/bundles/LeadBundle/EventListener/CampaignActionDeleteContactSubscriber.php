<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
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
use Mautic\CampaignBundle\Helper\RemovedContactTracker;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionDeleteContactSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var RemovedContactTracker
     */
    private $removedContactTracker;

    /**
     * CampaignActionDeleteContactSubscriber constructor.
     *
     * @param LeadModel             $leadModel
     * @param RemovedContactTracker $removedContactTracker
     */
    public function __construct(LeadModel $leadModel, RemovedContactTracker $removedContactTracker)
    {
        $this->leadModel             = $leadModel;
        $this->removedContactTracker = $removedContactTracker;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD             => ['configureAction', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_DELETE_CONTACT => ['deleteContacts', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function configureAction(CampaignBuilderEvent $event)
    {
        $event->addAction(
            'lead.deletecontact',
            [
                'label'                  => 'mautic.lead.lead.events.delete',
                'description'            => 'mautic.lead.lead.events.delete_descr',
                // Kept for BC in case plugins are listening to the shared trigger
                'eventName'              => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'batchEventName'         => LeadEvents::ON_CAMPAIGN_ACTION_DELETE_CONTACT,
                'connectionRestrictions' => [
                    'target' => [
                        'decision'  => ['none'],
                        'action'    => ['none'],
                        'condition' => ['none'],
                    ],
                ],
            ]
        );
    }

    /**
     * @param PendingEvent $event
     */
    public function deleteContacts(PendingEvent $event)
    {
        $contactIds = $event->getContactIds();

        $this->removedContactTracker->addRemovedContacts(
            $event->getEvent()->getCampaign()->getId(),
            $contactIds
        );

        $this->leadModel->deleteEntities($contactIds);

        $event->passAll();
    }
}
