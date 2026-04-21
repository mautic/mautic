<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\CompanySegment;

class UpdateCompanySegmentsCommandCircularDependencyTest extends MauticMysqlTestCase
{
    /**
     * @var array<string, CompanySegment>
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
            'mautic:company-segments:update',
            [
                '-i'               => $segmentA->getId(),
                '--bypass-locking' => true,
            ]
        );
    }

    private function createCircularDependencySegments(): void
    {
        $segmentA = $this->createSegment('Segment A');
        $segmentB = $this->createSegment('Segment B');
        $segmentC = $this->createSegment('Segment C');

        $this->em->flush();

        $this->addSegmentDependency($segmentA, $segmentB);
        $this->addSegmentDependency($segmentB, $segmentC);
        $this->addSegmentDependency($segmentC, $segmentA);

        $this->em->flush();

        $this->segments = [
            'Segment A' => $segmentA,
            'Segment B' => $segmentB,
            'Segment C' => $segmentC,
        ];
    }

    private function createSegment(string $name): CompanySegment
    {
        $segment = new CompanySegment();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(strtolower(str_replace(' ', '-', $name)));
        $segment->setIsPublished(true);

        $this->em->persist($segment);

        return $segment;
    }

    private function addSegmentDependency(CompanySegment $segment, CompanySegment $includeSegment): void
    {
        $filters   = $segment->getFilters();
        $filters[] = [
            'glue'     => 'and',
            'field'    => 'company_segments',
            'object'   => 'company',
            'type'     => 'company_segments',
            'filter'   => [$includeSegment->getId()],
            'display'  => null,
            'operator' => 'in',
        ];

        $segment->setFilters($filters);
        $this->em->persist($segment);
    }

    public function testSkippingNonExistentDependentSegment(): void
    {
        $segmentA = $this->createSegment('Segment A');
        $segmentB = $this->createSegment('Segment B');
        $this->em->flush();

        $this->addSegmentDependency($segmentA, $segmentB);

        // Add a non-existent segment ID as a dependency for segment A
        $nonExistentSegmentId = 9999;
        $filters              = $segmentA->getFilters();
        $filters[]            = [
            'glue'     => 'and',
            'field'    => 'company_segments',
            'object'   => 'company',
            'type'     => 'company_segments',
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

        $output = $this->testSymfonyCommand(
            'mautic:company-segments:update',
            [
                '-i'               => $segmentA->getId(),
                '--bypass-locking' => true,
            ]
        );

        $this->assertStringContainsString(
            sprintf('Rebuilding company segments for segment %d', $segmentB->getId()),
            $output->getDisplay()
        );

        $this->assertStringContainsString(
            sprintf('Rebuilding company segments for segment %d', $segmentA->getId()),
            $output->getDisplay()
        );

        $this->assertStringNotContainsString('error', strtolower($output->getDisplay()));
    }
}
