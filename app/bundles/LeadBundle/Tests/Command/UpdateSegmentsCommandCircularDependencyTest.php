<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\UpdateSegmentsCommand;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\LeadList;

class UpdateSegmentsCommandCircularDependencyTest extends MauticMysqlTestCase
{
    public function testLeadSegmentCircularDependencyDetection(): void
    {
        $segmentA = $this->createLeadSegment('Segment A');
        $segmentB = $this->createLeadSegment('Segment B');
        $segmentC = $this->createLeadSegment('Segment C');

        $this->em->flush();

        $this->addLeadSegmentDependency($segmentA, $segmentB);
        $this->addLeadSegmentDependency($segmentB, $segmentC);
        $this->addLeadSegmentDependency($segmentC, $segmentA);

        $this->em->flush();

        $this->expectException(\Mautic\LeadBundle\Segment\Exception\SegmentQueryException::class);
        $this->expectExceptionMessage('Circular reference detected.');

        $this->testSymfonyCommand(
            UpdateSegmentsCommand::NAME,
            [
                '-i'    => $segmentA->getId(),
                '--env' => 'test',
            ]
        );
    }

    public function testLeadSegmentSkippingNonExistentDependentSegment(): void
    {
        $segmentA = $this->createLeadSegment('Segment A');
        $segmentB = $this->createLeadSegment('Segment B');

        $this->em->flush();

        $this->addLeadSegmentDependency($segmentA, $segmentB);

        $nonExistentSegmentId = 9999;
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

        $output = $this->testSymfonyCommand(
            UpdateSegmentsCommand::NAME,
            [
                '-i'    => $segmentA->getId(),
                '--env' => 'test',
            ]
        );

        $this->assertStringContainsString(
            sprintf('Rebuilding contacts for segment %d', $segmentB->getId()),
            $output->getDisplay()
        );

        $this->assertStringContainsString(
            sprintf('Rebuilding contacts for segment %d', $segmentA->getId()),
            $output->getDisplay()
        );

        $this->assertStringNotContainsString('error', strtolower($output->getDisplay()));
    }

    public function testCompanySegmentCircularDependencyDetection(): void
    {
        $segmentA = $this->createCompanySegment('Segment A');
        $segmentB = $this->createCompanySegment('Segment B');
        $segmentC = $this->createCompanySegment('Segment C');

        $this->em->flush();

        $this->addCompanySegmentDependency($segmentA, $segmentB);
        $this->addCompanySegmentDependency($segmentB, $segmentC);
        $this->addCompanySegmentDependency($segmentC, $segmentA);

        $this->em->flush();

        $this->expectException(\Mautic\LeadBundle\Segment\Exception\SegmentQueryException::class);
        $this->expectExceptionMessage('Circular reference detected.');

        $this->testSymfonyCommand(
            UpdateSegmentsCommand::NAME,
            [
                '--companysegment-id' => $segmentA->getId(),
                '--bypass-locking'    => true,
            ]
        );
    }

    public function testCompanySegmentSkippingNonExistentDependentSegment(): void
    {
        $segmentA = $this->createCompanySegment('Segment A');
        $segmentB = $this->createCompanySegment('Segment B');

        $this->em->flush();

        $this->addCompanySegmentDependency($segmentA, $segmentB);

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

        $output = $this->testSymfonyCommand(
            UpdateSegmentsCommand::NAME,
            [
                '--companysegment-id' => $segmentA->getId(),
                '--bypass-locking'    => true,
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

    private function createLeadSegment(string $name): LeadList
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

    private function addLeadSegmentDependency(LeadList $segment, LeadList $includeSegment): void
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

    private function createCompanySegment(string $name): CompanySegment
    {
        $segment = new CompanySegment();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(strtolower(str_replace(' ', '-', $name)));
        $segment->setIsPublished(true);

        $this->em->persist($segment);

        return $segment;
    }

    private function addCompanySegmentDependency(CompanySegment $segment, CompanySegment $includeSegment): void
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
}
