<?php

namespace Mautic\CampaignBundle\Tests\Membership\Action;

use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Remover;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepository;

    /**
     * @var LeadEventLogRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadEventLogRepository;

    protected function setUp(): void
    {
        $this->leadRepository         = $this->createMock(LeadRepository::class);
        $this->leadEventLogRepository = $this->createMock(LeadEventLogRepository::class);
    }

    public function testMemberHasDateExitedSetWithForcedExit(): void
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(false);

        $this->leadEventLogRepository->expects($this->once())
            ->method('unscheduleEvents');

        $this->getRemover()->updateExistingMembership($campaignMember, true);

        $this->assertInstanceOf(\DateTime::class, $campaignMember->getDateLastExited());
    }

    public function testMemberHasDateExistedSetToNullWhenRemovedByFilter(): void
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(false);

        $this->leadEventLogRepository->expects($this->once())
            ->method('unscheduleEvents');

        $this->getRemover()->updateExistingMembership($campaignMember, false);

        $this->assertNull($campaignMember->getDateLastExited());
    }

    public function testExceptionThrownWhenMemberIsAlreadyRemoved(): void
    {
        $this->expectException(ContactAlreadyRemovedFromCampaignException::class);

        $campaignMember = new CampaignMember();
        $campaignMember->setManuallyRemoved(true);

        $this->getRemover()->updateExistingMembership($campaignMember, false);
    }

    /**
     * @return Remover
     */
    private function getRemover()
    {
        $translator     = $this->createMock(TranslatorInterface::class);
        $dateTimeHelper = new DateHelper(
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'H:i',
            $translator,
            $this->createMock(\Mautic\CoreBundle\Helper\CoreParametersHelper::class)
        );

        return new Remover($this->leadRepository, $this->leadEventLogRepository, $translator, $dateTimeHelper);
    }
}
