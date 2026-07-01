<?php

namespace Mautic\CampaignBundle\Tests\Membership\Action;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;

final class AdderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LeadRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LeadEventLogRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadEventLogRepository;

    protected function setUp(): void
    {
        $this->leadRepository         = $this->createMock(LeadRepository::class);
        $this->leadEventLogRepository = $this->createMock(LeadEventLogRepository::class);
    }

    public function testNewMemberAdded(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);
        $campaign->method('allowRestart')
            ->willReturn(true);

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->WillReturn(2);

        $this->leadEventLogRepository->method('hasBeenInCampaignRotation')
            ->with(2, 1, 1)
            ->willReturn(true);

        $this->leadRepository->expects($this->once())
            ->method('saveEntity');

        $campaignMember = $this->getAdder()->createNewMembership($contact, $campaign, true);

        $this->assertEquals($contact, $campaignMember->getLead());
        $this->assertEquals($campaign, $campaignMember->getCampaign());
        $this->assertEquals(true, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testManuallyRemovedAddedBackWhenManualActionAddsTheMember(): void
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaign = new Campaign();
        $campaign->setAllowRestart(true);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, true);

        $this->assertEquals(true, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testFilterRemovedAddedBackWhenManualActionAddsTheMember(): void
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaignMember->setDateLastExited(new \DateTime());
        $campaign = new Campaign();
        $campaign->setAllowRestart(true);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, false);

        $this->assertEquals(false, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testManuallyRemovedIsNotAddedBackWhenFilterActionAddsTheMember(): void
    {
        $this->expectException(ContactCannotBeAddedToCampaignException::class);

        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaign = new Campaign();
        $campaign->setAllowRestart(false);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, false);
    }

    public function testManuallyRemovedCanBeAddedBackByManualActionWhenRestartIsDisabled(): void
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setRotation(1);
        $campaign = new Campaign();
        $campaign->setAllowRestart(false);
        $campaignMember->setCampaign($campaign);

        $this->getAdder()->updateExistingMembership($campaignMember, true);

        $this->assertEquals(true, $campaignMember->wasManuallyAdded());
        $this->assertEquals(2, $campaignMember->getRotation());
    }

    public function testNaturallyExitedContactCannotBeAddedBackByCampaignActionWhenRestartIsDisabled(): void
    {
        $this->expectException(ContactCannotBeAddedToCampaignException::class);

        $campaignMember = new CampaignMember();
        // Natural exits are marked as removed with an exit date.
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setDateLastExited(new \DateTime());
        $campaignMember->setRotation(1);
        $campaign = new Campaign();
        $campaign->setAllowRestart(false);
        $campaignMember->setCampaign($campaign);

        // Campaign action "add to campaign" uses manual=true, which was the regression path.
        $this->getAdder()->updateExistingMembership($campaignMember, true);
    }

    private function getAdder(): Adder
    {
        return new Adder($this->leadRepository, $this->leadEventLogRepository);
    }
}
