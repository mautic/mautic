<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType;
use Mautic\CampaignBundle\Form\Validator\Constraints\InfiniteLoopValidator;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityValidateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionChangeMembershipSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MembershipManager $membershipManager,
        private CampaignModel $campaignModel,
        private InfiniteLoopValidator $infiniteLoopValidator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                    => ['addAction', 0],
            CampaignEvents::ON_CAMPAIGN_ACTION_CHANGE_MEMBERSHIP => ['changeMembership', 0],
            EntityValidateEvent::class                           => ['validateInfiniteLoop', 0],
        ];
    }

    /**
     * Add change membership action.
     */
    public function addAction(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'campaign.addremovelead',
            [
                'label'           => 'mautic.campaign.event.addremovelead',
                'description'     => 'mautic.campaign.event.addremovelead_descr',
                'formType'        => CampaignEventAddRemoveLeadType::class,
                'formTypeOptions' => [
                    'include_this' => true,
                ],
                'batchEventName'  => CampaignEvents::ON_CAMPAIGN_ACTION_CHANGE_MEMBERSHIP,
            ]
        );
    }

    public function changeMembership(PendingEvent $event): void
    {
        $properties          = $event->getEvent()->getProperties();
        $contacts            = $event->getContactsKeyedById();
        $executingCampaign   = $event->getEvent()->getCampaign();

        if (!empty($properties['addTo'])) {
            $campaigns = $this->getCampaigns($properties['addTo'], $executingCampaign);

            /** @var Campaign $campaign */
            foreach ($campaigns as $campaign) {
                $this->membershipManager->addContacts(
                    $contacts,
                    $campaign,
                    true
                );
            }
        }

        if (!empty($properties['removeFrom'])) {
            $campaigns = $this->getCampaigns($properties['removeFrom'], $executingCampaign);

            /** @var Campaign $campaign */
            foreach ($campaigns as $campaign) {
                $this->membershipManager->removeContacts(
                    $event->getContactsKeyedById(),
                    $campaign,
                    true
                );
            }
        }

        $event->passAll();
    }

    public function validateInfiniteLoop(EntityValidateEvent $event): void
    {
        $campaignEvent = $event->getEntity();

        if (!$campaignEvent instanceof Event) {
            return;
        }

        if ('campaign.addremovelead' !== $campaignEvent->getType()) {
            return;
        }

        $this->infiniteLoopValidator->validateEvent(
            $event->getContext(),
            $campaignEvent->getTriggerMode(),
            $campaignEvent->getProperties()['addTo'],
            $campaignEvent->getTriggerInterval(),
            $campaignEvent->getTriggerIntervalUnit()
        );
    }

    /**
     * @return array
     */
    private function getCampaigns(array $campaigns, Campaign $executingCampaign)
    {
        // Check for the keyword "this"
        $includeExecutingCampaign = false;
        $key                      = array_search('this', $campaigns);
        if (false !== $key) {
            $includeExecutingCampaign = true;
            // Remove it from the list of IDs
            unset($campaigns[$key]);
        }

        $campaignEntities = [];
        if (!empty($campaigns)) {
            $campaignEntities = $this->campaignModel->getEntities(['ids' => $campaigns, 'ignore_paginator' => true]);
        }

        // Include executing campaign if the keyword this was used
        if ($includeExecutingCampaign) {
            $campaignEntities[] = $executingCampaign;
        }

        return $campaignEntities;
    }
}
