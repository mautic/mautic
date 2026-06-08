<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\LeadList;

class UpdateLeadListsCommandCircularDependencyTest extends MauticMysqlTestCase
{
    /**
     * @var LeadList[]
     */
    private array $segments = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCircularDependencySegments();
    }

    public function testCircularDependencyDetection(): void
    {
        $segmentA = $this->segments['Segment A'];

        $this->expectException(\Mautic\LeadBundle\Segment\Exception\SegmentQueryException::class);
        $this->expectExceptionMessage('Circular reference detected.');

        $this->testSymfonyCommand(
            UpdateLeadListsCommand::NAME,
            [
                '-i'    => $segmentA->getId(),
                '--env' => 'test',
            ]
        );
    }

    /**
     * Creates segments with circular dependencies:
     * Segment A includes Segment B
     * Segment B includes Segment C
     * Segment C includes Segment A
     */
    private function createCircularDependencySegments(): void
    {
        // Create three test segments
        $segmentA = $this->createSegment('Segment A');
        $segmentB = $this->createSegment('Segment B');
        $segmentC = $this->createSegment('Segment C');

        $this->em->flush();

        // Add filters to create circular dependencies
        // Segment A includes Segment B
        $this->addSegmentDependency($segmentA, $segmentB);

        // Segment B includes Segment C
        $this->addSegmentDependency($segmentB, $segmentC);

        // Segment C includes Segment A (creating the circular dependency)
        $this->addSegmentDependency($segmentC, $segmentA);

        $this->em->flush();

        // Store segments for later use
        $this->segments = [
            'Segment A' => $segmentA,
            'Segment B' => $segmentB,
            'Segment C' => $segmentC,
        ];
    }

    private function createSegment(string $name): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(strtolower(str_replace(' ', '-', $name)));
        $segment->setIsGlobal(true);
        $segment->setIsPublished(true);

        $this->em->persist($segment);

        return $segment;
    }

    private function addSegmentDependency(LeadList $segment, LeadList $includeSegment): void
    {
        $filters   = $segment->getFilters();
        $filters[] = [
            'glue'     => 'and',
            'field'    => 'leadlist',
            'object'   => 'lead',
            'type'     => 'leadlist',
            'filter'   => [$includeSegment->getId()],
            'display'  => null,
            'operator' => 'in',
        ];

        $segment->setFilters($filters);
        $this->em->persist($segment);
    }

    public function testSkippingNonExistentDependentSegment(): void
    {
        // Create two segments (A and B)
        $segmentA = $this->createSegment('Segment A');
        $segmentB = $this->createSegment('Segment B');
        $this->em->flush();

        // Add a filter to segment A that includes segment B
        $this->addSegmentDependency($segmentA, $segmentB);

        // Add a non-existent segment ID as a dependency for segment A
        $nonExistentSegmentId = 9999; // An ID that doesn't exist
        $filters              = $segmentA->getFilters();
        $filters[]            = [
            'glue'     => 'and',
            'field'    => 'leadlist',
            'object'   => 'lead',
            'type'     => 'leadlist',
            'filter'   => [$nonExistentSegmentId],
            'display'  => null,
            'operator' => 'in',
        ];
        $segmentA->setFilters($filters);

        $this->em->persist($segmentA);
        $this->em->flush();

        $this->segments = [
            'Segment A' => $segmentA,
            'Segment B' => $segmentB,
        ];

        // The command should complete without errors despite the non-existent segment dependency
        $output = $this->testSymfonyCommand(
            UpdateLeadListsCommand::NAME,
            [
                '-i'    => $segmentA->getId(),
                '--env' => 'test',
            ]
        );

        // Verify that segment B was processed
        $this->assertStringContainsString(
            sprintf('Rebuilding contacts for segment %d', $segmentB->getId()),
            $output->getDisplay()
        );

        // Verify that segment A was processed after its dependencies
        $this->assertStringContainsString(
            sprintf('Rebuilding contacts for segment %d', $segmentA->getId()),
            $output->getDisplay()
        );

        // Verify that the command completed successfully
        $this->assertStringNotContainsString('error', strtolower($output->getDisplay()));
    }
}
