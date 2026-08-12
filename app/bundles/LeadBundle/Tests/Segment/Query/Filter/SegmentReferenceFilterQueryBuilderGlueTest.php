<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class SegmentReferenceFilterQueryBuilderGlueTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testMultipleFiltersConnectedWithOrGlue(): void
    {
        $leadA = $this->createLead('A');
        $leadB = $this->createLead('B');
        $leadC = $this->createLead('C');
        $leadD = $this->createLead('D');

        $segmentA = $this->createSegment('A', []);
        $this->createListLead($segmentA, $leadA);
        $this->createListLead($segmentA, $leadD);

        $segmentB = $this->createSegment('B', []);
        $this->createListLead($segmentB, $leadB);
        $this->createListLead($segmentB, $leadD);

        $segmentC = $this->createSegment('C', []);
        $this->createListLead($segmentC, $leadC);
        $this->createListLead($segmentC, $leadD);

        $this->em->flush();

        $segmentD = $this->createSegment('D', [
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'leadlist',
                'type'       => 'leadlist',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [
                        $segmentA->getId(),
                    ],
                ],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'leadlist',
                'type'       => 'leadlist',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [
                        $segmentB->getId(),
                        $segmentC->getId(),
                    ],
                ],
            ],
        ]);

        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:segments:update', ['--list-id' => $segmentD->getId()]);

        /** @var ListModel $listModel */
        $listModel = self::getContainer()->get(ListModel::class);

        $leadCount = $listModel->getListLeadRepository()->getContactsCountBySegment($segmentD->getId());
        $this->assertSame(4, $leadCount, 'Segment must contain all the leads.');
    }
}
