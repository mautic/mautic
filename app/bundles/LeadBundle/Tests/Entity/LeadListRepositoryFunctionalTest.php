<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\ListLead;

final class LeadListRepositoryFunctionalTest extends AbstractMauticTestCase
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

    public function testSegmentTypeSearchCommands(): void
    {
        $dynamicFilters = [
            [
                'object'   => 'lead',
                'glue'     => 'and',
                'field'    => 'email',
                'type'     => 'email',
                'operator' => '=',
                'filter'   => 'dynamic@example.com',
            ],
        ];

        $dynamicSegment = $this->createSegment('DynamicSearch', $dynamicFilters);
        $staticSegment  = $this->createSegment('StaticSearch');

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $commands = $leadListRepository->getSearchCommands();
        $this->assertContains('mautic.lead.list.searchcommand.dynamic', $commands);
        $this->assertContains('mautic.lead.list.searchcommand.static', $commands);

        $dynamicResults = $leadListRepository->getEntities([
            'filter'           => ['string' => sprintf('ids:%d is:dynamic', $dynamicSegment->getId())],
            'ignore_paginator' => true,
        ]);
        $dynamicIds = array_map('intval', array_keys($dynamicResults));
        $this->assertSame([$dynamicSegment->getId()], $dynamicIds);

        $staticResults = $leadListRepository->getEntities([
            'filter'           => ['string' => sprintf('ids:%d is:static', $staticSegment->getId())],
            'ignore_paginator' => true,
        ]);
        $staticIds = array_map('intval', array_keys($staticResults));
        $this->assertSame([$staticSegment->getId()], $staticIds);
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

    private function createSegment(string $suffix = 'A', ?array $filters = null): LeadList
    {
        $segment = new LeadList();
        $segment->setName("Segment {$suffix}");
        $segment->setPublicName("Segment {$suffix}");
        $segment->setAlias("segment-{$suffix}");

        if (null !== $filters) {
            $segment->setFilters($filters);
        }

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
