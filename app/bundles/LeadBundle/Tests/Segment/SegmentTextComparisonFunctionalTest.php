<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Attributes\DataProvider;

final class SegmentTextComparisonFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<string, array{operator: string, filter: string|string[], expectedKeys: string[]}>
     */
    public static function textOperatorProvider(): iterable
    {
        yield 'eq folds sharp s to ss' => [
            'operator'     => OperatorOptions::EQUAL_TO,
            'filter'       => 'strasse',
            'expectedKeys' => ['sharpS'],
        ];
        yield 'eq ignores accents and case' => [
            'operator'     => OperatorOptions::EQUAL_TO,
            'filter'       => 'JOSE',
            'expectedKeys' => ['accent'],
        ];
        yield 'in uses text collation for each value' => [
            'operator'     => OperatorOptions::INCLUDING_ANY,
            'filter'       => ['STRASSE', 'jose'],
            'expectedKeys' => ['sharpS', 'accent'],
        ];
        yield 'neq excludes collation-equal values only' => [
            'operator'     => OperatorOptions::NOT_EQUAL_TO,
            'filter'       => 'STRASSE',
            'expectedKeys' => ['accent', 'other', 'plain'],
        ];
    }

    #[DataProvider('textOperatorProvider')]
    public function testTextOperatorsUseCaseAndAccentInsensitiveComparison(string $operator, string|array $filter, array $expectedKeys): void
    {
        $leads = [
            'sharpS' => $this->createLead('Straße', 120),
            'accent' => $this->createLead('José', 85),
            'other'  => $this->createLead('Müller', 120),
            'plain'  => $this->createLead('Other', 85),
        ];
        $this->em->flush();

        $expectedIds = array_map(static fn (string $key): int => $leads[$key]->getId(), $expectedKeys);
        $this->assertSegmentMembers('firstname', $operator, $filter, $expectedIds);
    }

    public function testNumericSegmentEqualityRemainsNumeric(): void
    {
        $matching = $this->createLead('Numeric Match', 120);
        $this->createLead('Numeric Other', 121);
        $this->em->flush();

        $this->assertSegmentMembers('points', OperatorOptions::EQUAL_TO, 120, [$matching->getId()]);
    }

    private function createLead(string $firstName, int $points): Lead
    {
        $lead = new Lead();
        $lead->setEmail('text-comparison-'.bin2hex(random_bytes(8)).'@example.test');
        $lead->setFirstname($firstName);
        $lead->setPoints($points);
        $this->em->persist($lead);

        return $lead;
    }

    /**
     * @param string|int|string[] $filter
     * @param int[]               $expectedIds
     */
    private function assertSegmentMembers(string $field, string $operator, string|int|array $filter, array $expectedIds): void
    {
        $segment = new LeadList();
        $segment->setName('Text comparison '.$field.' '.$operator.' '.bin2hex(random_bytes(4)))
            ->setPublicName('Text comparison')
            ->setAlias('text_comparison_'.bin2hex(random_bytes(8)))
            ->setIsPublished(true)
            ->setFilters([
                [
                    'glue'     => 'and',
                    'field'    => $field,
                    'object'   => 'lead',
                    'type'     => 'points' === $field ? 'number' : 'text',
                    'operator' => $operator,
                    'filter'   => $filter,
                    'display'  => '',
                ],
            ]);
        $this->em->persist($segment);
        $this->em->flush();

        /** @var ContactSegmentService $segmentService */
        $segmentService = self::getContainer()->get(ContactSegmentService::class);
        $results        = $segmentService->getNewLeadListLeads($segment, []);
        $actualIds      = array_map(intval(...), array_column($results[$segment->getId()], 'id'));
        sort($actualIds);
        sort($expectedIds);

        $this->assertSame($expectedIds, $actualIds);
    }
}
