<?php

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignLeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\NullLogger;

class InactiveContactFinderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadRepository
     */
    private $leadRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CampaignLeadRepository
     */
    private $campaignLeadRepository;

    protected function setUp(): void
    {
        $this->leadRepository         = $this->createMock(LeadRepository::class);
        $this->campaignLeadRepository = $this->createMock(CampaignLeadRepository::class);
    }

    public function testNoContactsFoundExceptionIsThrown()
    {
        $this->campaignLeadRepository->expects($this->once())
            ->method('getInactiveContacts')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, new Event(), $limiter);
    }

    public function testNoContactsFoundExceptionIsThrownIfEntitiesAreNotFound()
    {
        $contactMemberDates = [
            1 => new \DateTime(),
        ];

        $this->campaignLeadRepository->expects($this->once())
            ->method('getInactiveContacts')
            ->willReturn($contactMemberDates);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn([]);

        $this->expectException(NoContactsFoundException::class);

        $limiter = new ContactLimiter(0, 0, 0, 0);
        $this->getContactFinder()->getContacts(1, new Event(), $limiter);
    }

    public function testContactsAreFoundAndStoredInCampaignMemberDatesAdded()
    {
        $contactMemberDates = [
            1 => new \DateTime(),
        ];

        $this->campaignLeadRepository->expects($this->once())
            ->method('getInactiveContacts')
            ->willReturn($contactMemberDates);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead()]));

        $contactFinder = $this->getContactFinder();

        $limiter  = new ContactLimiter(0, 0, 0, 0);
        $contacts = $contactFinder->getContacts(1, new Event(), $limiter);
        $this->assertCount(1, $contacts);

        $this->assertEquals($contactMemberDates, $contactFinder->getDatesAdded());
    }

    /**
     * @return InactiveContactFinder
     */
    private function getContactFinder()
    {
        return new InactiveContactFinder(
            $this->leadRepository,
            $this->campaignLeadRepository,
            new NullLogger()
        );
    }
}
