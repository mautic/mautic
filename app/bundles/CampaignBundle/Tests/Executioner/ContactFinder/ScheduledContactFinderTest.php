<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

final class ScheduledContactFinderTest extends TestCase
{
    private MockObject&LeadRepository $leadRepository;

    protected function setUp(): void
    {
        $this->leadRepository = $this->createMock(LeadRepository::class);
    }

    public function testHydratedLeadsFromRepositoryAreFoundAndPushedIntoLogs(): void
    {
        $lead1 = new Lead();
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setId(2);

        $log1 = new LeadEventLog();
        $log1->setLead($lead1);

        $log2 = new LeadEventLog();
        $log2->setLead($lead2);

        /** @var Collection<int, LeadEventLog> $logs */
        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $contacs = new ArrayCollection(
            [
                1 => $lead1,
                2 => $lead2,
            ]
        );

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn($contacs);

        $this->getContactFinder()->hydrateContacts($logs);

        $this->assertSame($lead1, $log1->getLead());
        $this->assertSame($lead2, $log2->getLead());
    }

    public function testHydratedLeadsFromRepositoryWithMissingLeadResultsLogBeingRemoved(): void
    {
        $lead1 = new Lead();
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setId(2);

        $log1 = new LeadEventLog();
        $log1->setLead($lead1);

        $log2 = new LeadEventLog();
        $log2->setLead($lead2);

        /** @var Collection<int, LeadEventLog> $logs */
        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $contacs = new ArrayCollection(
            [
                1 => $lead1,
            ]
        );

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn($contacs);

        $this->getContactFinder()->hydrateContacts($logs);

        $this->assertCount(1, $logs);
        $this->assertSame($lead1, $log1->getLead());
    }

    public function testNoContactsFoundExceptionIsThrownIfEntitiesAreNotFound(): void
    {
        $this->leadRepository->expects($this->never())
            ->method('getContactCollection');

        $this->expectException(NoContactsFoundException::class);

        $this->getContactFinder()->hydrateContacts(new ArrayCollection());
    }

    private function getContactFinder(): ScheduledContactFinder
    {
        return new ScheduledContactFinder(
            $this->leadRepository,
            new NullLogger()
        );
    }
}
