<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Form\Type\CampaignActionAddDNCType;
use Mautic\LeadBundle\Form\Type\CampaignActionRemoveDNCType;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionDNCSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DoNotContact $doNotContact,
        private LeadModel $leadModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['configureAction', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT    => ['addDoNotContact', 0],
            LeadEvents::ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT => ['removeDoNotContact', 0],
        ];
    }

    public function configureAction(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'lead.adddnc',
            [
                'label'          => 'mautic.lead.lead.events.add_donotcontact',
                'description'    => 'mautic.lead.lead.events.add_donotcontact_desc',
                'batchEventName' => LeadEvents::ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT,
                'formType'       => CampaignActionAddDNCType::class,
            ]
        );

        $event->addAction(
            'lead.removednc',
            [
                'label'          => 'mautic.lead.lead.events.remove_donotcontact',
                'description'    => 'mautic.lead.lead.events.remove_donotcontact_desc',
                'batchEventName' => LeadEvents::ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT,
                'formType'       => CampaignActionRemoveDNCType::class,
            ]
        );
    }

    public function addDoNotContact(PendingEvent $event): void
    {
        $config          = $event->getEvent()->getProperties();
        $channels        = ArrayHelper::getValue('channels', $config, []);
        $reason          = ArrayHelper::getValue('reason', $config, '');
        $persistEntities = [];
        $contacts        = $event->getContactsKeyedById();
        foreach ($contacts as $contactId=>$contact) {
            foreach ($channels as $channel) {
                $this->doNotContact->addDncForContact(
                    $contactId,
                    $channel,
                    \Mautic\LeadBundle\Entity\DoNotContact::MANUAL,
                    $reason,
                    false
                );
            }
            $persistEntities[] = $contact;
        }

        $this->leadModel->saveEntities($persistEntities);

        $event->passAll();
    }

    public function removeDoNotContact(PendingEvent $event): void
    {
        $config          = $event->getEvent()->getProperties();
        $channels        = ArrayHelper::getValue('channels', $config, []);
        $persistEntities = [];
        $contacts        = $event->getContactsKeyedById();
        foreach ($contacts as $contactId=>$contact) {
            foreach ($channels as $channel) {
                $this->doNotContact->removeDncForContact(
                    $contactId,
                    $channel,
                    true
                );
            }
            $persistEntities[] = $contact;
        }

        $this->leadModel->saveEntities($persistEntities);

        $event->passAll();
    }
}
