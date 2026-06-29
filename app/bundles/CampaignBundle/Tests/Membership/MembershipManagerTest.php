<?php

namespace Mautic\CampaignBundle\Tests\Membership;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\Action\Remover;
use Mautic\CampaignBundle\Membership\EventDispatcher;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;

class MembershipManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Adder|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $adder;

    /**
     * @var Remover|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $remover;

    /**
     * @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepository;

    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->adder           = $this->createMock(Adder::class);
        $this->remover         = $this->createMock(Remover::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->leadRepository  = $this->createMock(LeadRepository::class);
        $this->logger          = new NullLogger();
    }

    public function testMembershipCreatedIfNotFound(): void
    {
        $contact  = new Lead();
        $campaign = new Campaign();

        $this->leadRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->adder->expects($this->once())
            ->method('createNewMembership');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchMembershipChange');

        $this->getManager()->addContact($contact, $campaign);
    }

    public function testMembershipUpdatedIfFound(): void
    {
        $contact        = new Lead();
        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);

        $this->leadRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($campaignMember);

        $this->adder->expects($this->once())
            ->method('updateExistingMembership');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchMembershipChange');

        $this->getManager()->addContact($contact, $campaign);
    }

    public function testMembershipIsUpdatedWhenRemoved(): void
    {
        $contact        = new Lead();
        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);

        $this->leadRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($campaignMember);

        $this->remover->expects($this->once())
            ->method('updateExistingMembership');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchMembershipChange');

        $this->getManager()->removeContact($contact, $campaign);
    }

    public function testContactsAreAddedOrUpdated(): void
    {
        $contact = new class extends Lead {
            public function __construct(private readonly int $id = 1)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
        $contact2 = new class extends Lead {
            public function __construct(private readonly int $id = 2)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }
        };

        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact2);
        $campaignMember->setCampaign($campaign);

        // One is found and one is not
        $this->leadRepository->expects($this->once())
            ->method('getCampaignMembers')
            ->willReturn([$contact2->getId() => $campaignMember]);

        $this->adder->expects($this->once())
            ->method('updateExistingMembership')
            ->with($campaignMember, true);

        $this->adder->expects($this->once())
            ->method('createNewMembership')
            ->with($contact, $campaign, true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchBatchMembershipChange')
            ->with([$contact->getId() => $contact, $contact2->getId() => $contact2], $campaign, Adder::NAME);

        /** @var ArrayCollection<int, Lead> $contacts */
        $contacts = new ArrayCollection([1 => $contact, 2 => $contact2]);

        $this->getManager()->addContacts($contacts, $campaign);
    }

    public function testContactsAreRemoved(): void
    {
        $contact = new class extends Lead {
            public function __construct(private readonly int $id = 1)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
        $contact2 = new class extends Lead {
            public function __construct(private readonly int $id = 2)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }
        };

        $campaign       = new Campaign();
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact2);
        $campaignMember->setCampaign($campaign);

        // One is found and one is not
        $this->leadRepository->expects($this->once())
            ->method('getCampaignMembers')
            ->willReturn([$contact2->getId() => $campaignMember]);

        $this->remover->expects($this->once())
            ->method('updateExistingMembership')
            ->with($campaignMember, false);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchBatchMembershipChange')
            ->with([$contact2->getId() => $contact2], $campaign, Remover::NAME);

        /** @var ArrayCollection<int, Lead> $contacts */
        $contacts = new ArrayCollection([1 => $contact, 2 => $contact2]);

        $this->getManager()->removeContacts($contacts, $campaign);
    }

    private function getManager(): MembershipManager
    {
        return new MembershipManager($this->adder, $this->remover, $this->eventDispatcher, $this->leadRepository, $this->logger);
    }
}
