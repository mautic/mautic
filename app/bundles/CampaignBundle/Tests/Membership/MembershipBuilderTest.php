<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Membership;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignMemberRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

final class MembershipBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MembershipManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $manager;

    /**
     * @var CampaignMemberRepository|MockObject
     */
    private $campaignMemberRepository;

    /**
     * @var LeadRepository|MockObject
     */
    private $leadRepository;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var MembershipBuilder
     */
    private $membershipBuilder;

    protected function setUp(): void
    {
        $this->manager                  = $this->createMock(MembershipManager::class);
        $this->campaignMemberRepository = $this->createMock(CampaignMemberRepository::class);
        $this->leadRepository           = $this->createMock(LeadRepository::class);
        $this->translator               = $this->createMock(TranslatorInterface::class);
        $this->membershipBuilder        = new MembershipBuilder(
            $this->manager,
            $this->campaignMemberRepository,
            $this->leadRepository,
            $this->translator
        );
    }

    public function testContactCountIsSkippedWhenOutputIsNull(): void
    {
        $campaign       = new Campaign();
        $contactLimiter = new ContactLimiter(100);

        $this->campaignMemberRepository->expects($this->never())
            ->method('getCountsForCampaignContactsBySegment');

        $this->campaignMemberRepository->expects($this->never())
            ->method('getCountsForOrphanedContactsBySegments');

        $this->campaignMemberRepository->expects($this->once())
            ->method('getCampaignContactsBySegments')
            ->willReturn([]);

        $this->campaignMemberRepository->expects($this->once())
            ->method('getOrphanedContacts')
            ->willReturn([]);

        $this->membershipBuilder->build($campaign, $contactLimiter, 1000);
    }

    public function testContactsAreNotRemovedIfRunLimitReachedWhileAdding(): void
    {
        $campaign       = new Campaign();
        $contactLimiter = new ContactLimiter(100);

        $this->campaignMemberRepository->expects($this->once())
            ->method('getCampaignContactsBySegments')
            ->willReturn([20, 21, 22]);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead(), new Lead(), new Lead()]));

        $this->campaignMemberRepository->expects($this->never())
            ->method('getOrphanedContacts');

        $this->membershipBuilder->build($campaign, $contactLimiter, 2);
    }

    public function testWhileLoopBreaksWithNoMoreContacts(): void
    {
        $campaign = new class() extends Campaign {
            public function getId()
            {
                return 111;
            }
        };

        $contactLimiter = new ContactLimiter(1);

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getCampaignContactsBySegments')
            ->withConsecutive(
                [111, $contactLimiter, false],
                [111, $contactLimiter, false],
                [111, $contactLimiter, false],
                [111, $contactLimiter, false]
            )
            ->willReturnOnConsecutiveCalls([20], [21], [22], []);

        $this->manager->expects($this->exactly(3))
            ->method('addContacts');

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getOrphanedContacts')
            ->willReturnOnConsecutiveCalls([23], [24], [25], []);

        $this->manager->expects($this->exactly(3))
            ->method('removeContacts');

        $this->leadRepository->expects($this->exactly(6))
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->membershipBuilder->build($campaign, $contactLimiter, 100);
    }

    public function testWhileLoopBreaksWithNoMoreContactsForRepeatableCampaign(): void
    {
        $campaign = new class() extends Campaign {
            public function getId()
            {
                return 111;
            }
        };

        $campaign->setAllowRestart(true);

        $contactLimiter = new ContactLimiter(1);

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getCampaignContactsBySegments')
            ->withConsecutive(
                [111, $contactLimiter, true],
                [111, $contactLimiter, true],
                [111, $contactLimiter, true],
                [111, $contactLimiter, true]
            )
            ->willReturnOnConsecutiveCalls([20], [21], [22], []);

        $this->manager->expects($this->exactly(3))
            ->method('addContacts');

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getOrphanedContacts')
            ->willReturnOnConsecutiveCalls([23], [24], [25], []);

        $this->manager->expects($this->exactly(3))
            ->method('removeContacts');

        $this->leadRepository->expects($this->exactly(6))
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->membershipBuilder->build($campaign, $contactLimiter, 100);
    }
}
