<?php

namespace Mautic\LeadBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\Helper\RemovedContactTracker;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;

final readonly class CampaignActionDeleteContactSubscriber
{
    public function __construct(
        private LeadModel $leadModel,
        private RemovedContactTracker $removedContactTracker,
    ) {
    }

    #[AsEventListener(event: CampaignBuilderEvent::class)]
    public function configureAction(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'lead.deletecontact',
            [
                'label'                  => 'mautic.lead.lead.events.delete',
                'description'            => 'mautic.lead.lead.events.delete_descr',
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

    #[AsEventListener(event: LeadEvents::ON_CAMPAIGN_ACTION_DELETE_CONTACT)]
    public function deleteContacts(PendingEvent $event): void
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
