<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Membership;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MembershipBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MembershipManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $manager;

    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $campaignMemberRepository;

    /**
     * @var \Mautic\LeadBundle\Entity\LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadRepository;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    protected function setUp()
    {
        $this->manager                  = $this->createMock(MembershipManager::class);
        $this->campaignMemberRepository = $this->createMock(LeadRepository::class);
        $this->leadRepository           = $this->createMock(\Mautic\LeadBundle\Entity\LeadRepository::class);
        $this->eventDispatcher          = $this->createMock(EventDispatcherInterface::class);
        $this->translator               = $this->createMock(TranslatorInterface::class);
    }

    public function testContactCountIsSkippedWhenOutputIsNull()
    {
        $builder = $this->getBuilder();

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

        $builder->build($campaign, $contactLimiter, 1000);
    }

    public function testContactsAreNotRemovedIfRunLimitReachedWhileAdding()
    {
        $builder = $this->getBuilder();

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

        $builder->build($campaign, $contactLimiter, 2);
    }

    public function testWhileLoopBreaksWithNoMoreContacts()
    {
        $builder = $this->getBuilder();

        $campaign       = new Campaign();
        $contactLimiter = new ContactLimiter(1);

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getCampaignContactsBySegments')
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

        $builder->build($campaign, $contactLimiter, 100);
    }

    /**
     * @return MembershipBuilder
     */
    private function getBuilder()
    {
        return new MembershipBuilder(
            $this->manager,
            $this->campaignMemberRepository,
            $this->leadRepository,
            $this->eventDispatcher,
            $this->translator
        );
    }
}
