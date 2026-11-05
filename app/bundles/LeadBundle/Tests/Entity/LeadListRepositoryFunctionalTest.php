<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\ListLead;

final class LeadListRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testCheckLeadSegmentsByIds(): void
    {
        $lead     = $this->createLead();
        $segmentA = $this->createSegment();
        $segmentB = $this->createSegment('B');
        $segmentC = $this->createSegment('C');
        $this->createSegmentMember($segmentA, $lead);
        $this->createSegmentMember($segmentB, $lead, true);

        $leadListRepository = self::getContainer()->get(LeadListRepository::class);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentA->getId()]);
        $this->assertTrue($result);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentB->getId()]);
        $this->assertFalse($result);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentC->getId()]);
        $this->assertFalse($result);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentA->getId(), $segmentB->getId(), $segmentC->getId()]);
        $this->assertTrue($result);
    }

    public function testGetSegmentStatisticsAggregatesActiveLeadSources(): void
    {
        $segment = $this->createSegment();
        $manuallyAddedLead = $this->createLead('manual@example.com');
        $filterAddedLead = $this->createLead('filter@example.com');
        $removedLead = $this->createLead('removed@example.com');

        $this->createSegmentMember($segment, $manuallyAddedLead, false, true);
        $this->createSegmentMember($segment, $filterAddedLead);
        $this->createSegmentMember($segment, $removedLead, true, true);

        $repository = self::getContainer()->get(LeadListRepository::class);

        $this->assertSame(
            ['total' => 2, 'manuallyAdded' => 1, 'filterAdded' => 1],
            $repository->getSegmentStatistics($segment->getId(), true)
        );
        $this->assertSame(
            ['total' => 2, 'manuallyAdded' => 1, 'filterAdded' => null],
            $repository->getSegmentStatistics($segment->getId(), false)
        );
    }

    private function createLead(string $email = 'test@test.com'): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Contact');
        $lead->setEmail($email);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createSegment(string $suffix = 'A'): LeadList
    {
        $segment = new LeadList();
        $segment->setName("Segment {$suffix}");
        $segment->setPublicName("Segment {$suffix}");
        $segment->setAlias("segment-{$suffix}");

        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    protected function createSegmentMember(LeadList $segment, Lead $lead, bool $isManuallyRemoved = false, bool $isManuallyAdded = false): void
    {
        $segmentMember = new ListLead();
        $segmentMember->setLead($lead);
        $segmentMember->setList($segment);
        $segmentMember->setManuallyRemoved($isManuallyRemoved);
        $segmentMember->setManuallyAdded($isManuallyAdded);
        $segmentMember->setDateAdded(new \DateTime());
        $this->em->persist($segmentMember);
        $this->em->flush();
    }
}
