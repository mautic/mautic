<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CampaignActionChangeMembershipSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public function testDispatchesAfterEventWhenContactIsRemovedFromCampaign(): void
    {
        $contact           = $this->createContact();
        $executingCampaign = $this->createCampaign('Executing campaign');
        $targetCampaign    = $this->createCampaign('Target campaign');

        $targetMembership = new CampaignLead();
        $targetMembership->setCampaign($targetCampaign);
        $targetMembership->setLead($contact);
        $targetMembership->setDateAdded(new \DateTime());

        $this->em->persist($targetMembership);
        $this->em->flush();

        $campaignAction = new Event();
        $campaignAction->setCampaign($executingCampaign);
        $campaignAction->setName('Remove from campaign');
        $campaignAction->setType('campaign.addremovelead');
        $campaignAction->setEventType(Event::TYPE_ACTION);
        $campaignAction->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $campaignAction->setProperties([
            'removeFrom' => [$targetCampaign->getId()],
        ]);

        $leadEventLog = new LeadEventLog();
        $leadEventLog->setCampaign($executingCampaign);
        $leadEventLog->setEvent($campaignAction);
        $leadEventLog->setLead($contact);

        $pendingEvent = new PendingEvent(new ActionAccessor([]), $campaignAction, new ArrayCollection([$leadEventLog]));

        $dispatcher = static::getContainer()->get('event_dispatcher');
        \assert($dispatcher instanceof EventDispatcherInterface);

        $dispatchedCampaignIds = [];
        $listener              = static function (CampaignEvent $event) use (&$dispatchedCampaignIds): void {
            $dispatchedCampaignIds[] = $event->getCampaign()->getId();
        };

        $dispatcher->addListener(CampaignEvents::ON_AFTER_CAMPAIGN_ACTION_CHANGE_MEMBERSHIP, $listener);

        try {
            $dispatcher->dispatch($pendingEvent, CampaignEvents::ON_CAMPAIGN_ACTION_CHANGE_MEMBERSHIP);
        } finally {
            $dispatcher->removeListener(CampaignEvents::ON_AFTER_CAMPAIGN_ACTION_CHANGE_MEMBERSHIP, $listener);
        }

        $this->em->clear();

        /** @var CampaignLead|null $updatedMembership */
        $updatedMembership = $this->em->getRepository(CampaignLead::class)->findOneBy([
            'campaign' => $targetCampaign->getId(),
            'lead'     => $contact->getId(),
        ]);

        Assert::assertNotNull($updatedMembership);
        Assert::assertTrue($updatedMembership->getManuallyRemoved());
        Assert::assertSame([$targetCampaign->getId()], $dispatchedCampaignIds);
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('change-membership-test@example.com');

        $this->em->persist($contact);

        return $contact;
    }

    private function createCampaign(string $name): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name);
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);

        return $campaign;
    }
}
