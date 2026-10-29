<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
final class KickoffContactFinderTest extends TestCase
{
    /**
     * @var MockObject&LeadRepository
     */
    private MockObject $leadRepository;

    /**
     * @var MockObject&CampaignRepository
     */
    private MockObject $campaignRepository;

    protected function setUp(): void
    {
        $this->leadRepository = $this->createMock(LeadRepository::class);

        $this->campaignRepository = $this->createMock(CampaignRepository::class);
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

    private function getContactFinder(): KickoffContactFinder
    {
        return new KickoffContactFinder(
            $this->leadRepository,
            $this->campaignRepository,
            new NullLogger()
        );
    }
}
