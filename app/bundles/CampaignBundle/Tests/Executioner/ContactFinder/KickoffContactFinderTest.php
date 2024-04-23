<?php

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\NullLogger;

class KickoffContactFinderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CampaignRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $campaignRepository;

    protected function setUp(): void
    {
        $this->leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->campaignRepository = $this->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoContactsFoundExceptionIsThrown(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('getPendingContactIds')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, $limiter);
    }

    public function testNoContactsFoundExceptionIsThrownIfEntitiesAreNotFound(): void
    {
        $contactIds = [1, 2];

        $this->campaignRepository->expects($this->once())
            ->method('getPendingContactIds')
            ->willReturn($contactIds);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([]));

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, $limiter);
    }

    public function testArrayCollectionIsReturnedForFoundContacts(): void
    {
        $contactIds = [1, 2];

        $this->campaignRepository->expects($this->once())
            ->method('getPendingContactIds')
            ->willReturn($contactIds);

        $foundContacts = new ArrayCollection([new Lead(), new Lead()]);
        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn($foundContacts);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->assertEquals($foundContacts, $this->getContactFinder()->getContacts(1, $limiter));
    }

    /**
     * @return KickoffContactFinder
     */
    private function getContactFinder()
    {
        return new KickoffContactFinder(
            $this->leadRepository,
            $this->campaignRepository,
            new NullLogger()
        );
    }
}
