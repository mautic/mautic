<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;

class LeadListRepositoryFunctionalTest extends AbstractMauticTestCase
{
    public function testCheckLeadSegmentsByIds(): void
    {
        $lead     = $this->createLead();
        $segmentA = $this->createSegment();
        $segmentB = $this->createSegment('B');
        $segmentC = $this->createSegment('C');
        $this->createSegmentMember($segmentA, $lead);
        $this->createSegmentMember($segmentB, $lead, true);

        $leadListRepository = $this->em->getRepository(LeadList::class);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentA->getId()]);
        $this->assertTrue($result);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentB->getId()]);
        $this->assertFalse($result);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentC->getId()]);
        $this->assertFalse($result);

        $result = $leadListRepository->checkLeadSegmentsByIds($lead, [$segmentA->getId(), $segmentB->getId(), $segmentC->getId()]);
        $this->assertTrue($result);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Contact');
        $lead->setEmail('test@test.com');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createSegment(string $suffix = 'A'): LeadList
    {
        $segment = new LeadList();
        $segment->setName("Segment $suffix");
        $segment->setPublicName("Segment $suffix");
        $segment->setAlias("segment-$suffix");

        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    protected function createSegmentMember(LeadList $segment, Lead $lead, bool $isManuallyRemoved = false): void
    {
        $segmentMember = new ListLead();
        $segmentMember->setLead($lead);
        $segmentMember->setList($segment);
        $segmentMember->setManuallyRemoved($isManuallyRemoved);
        $segmentMember->setDateAdded(new \DateTime());
        $this->em->persist($segmentMember);
        $this->em->flush();
    }
}
