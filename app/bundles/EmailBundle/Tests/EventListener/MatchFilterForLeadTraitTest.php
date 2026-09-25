<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MatchFilterForLeadTraitTest extends TestCase
{
    /**
     * @var mixed[]
     */
    private array $lead = [
        'id'     => 1,
        'custom' => 'my custom text',
    ];

    /**
     * @var mixed[]
     */
    private array $filter = [
        0 => [
            'display' => null,
            'field'   => 'custom',
            'glue'    => 'and',
            'object'  => 'lead',
            'type'    => 'text',
        ],
    ];

    private MatchFilterForLeadTraitTestable $matchFilterForLeadTrait;

    protected function setUp(): void
    {
        $this->matchFilterForLeadTrait = new MatchFilterForLeadTraitTestable();

        // Set required environment variable for FormFieldHelper
        if (!isset($_ENV['MAUTIC_UPLOAD_DIR'])) {
            $_ENV['MAUTIC_UPLOAD_DIR'] = '/tmp';
        }
    }

    /**
     * @param string|array<int, string> $filter
     */
    #[DataProvider('regionFilterDataProvider')]
    public function testMatchFilterForLeadWithRegionFilter(string|array $filter, string $operator, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->matchFilterForLeadTrait->match(
                [
                    [
                        'glue'     => 'and',
                        'type'     => 'region',
                        'object'   => 'lead',
                        'field'    => 'region',
                        'operator' => $operator,
                        'filter'   => $filter,
                    ],
                ],
                [
                    'id'     => 123,
                    'region' => 'California',
                ]
            )
        );
    }

    /**
     * @return iterable<string, array{filter: string|string[], operator: string, expected: bool}>
     */
    public static function regionFilterDataProvider(): iterable
    {
        yield 'Region equals by state name is the same' => [
            'filter'   => 'California',
            'operator' => '=',
            'expected' => true,
        ];

        yield 'Region equals by state ID is the same' => [
            'filter'   => '4', // index starts at 0 for single select
            'operator' => '=',
            'expected' => true,
        ];

        yield 'Region equals by state name is NOT the same' => [
            'filter'   => 'Texas',
            'operator' => '=',
            'expected' => false,
        ];

        yield 'Region equals by state ID is NOT the same' => [
            'filter'   => '555',
            'operator' => '=',
            'expected' => false,
        ];

        yield 'Region including by state name is the same' => [
            'filter'   => ['California'],
            'operator' => 'in',
            'expected' => true,
        ];

        yield 'Region including by state ID is the same' => [
            'filter'   => ['5'], // index starts at 1 for muli-select
            'operator' => 'in',
            'expected' => true,
        ];

        yield 'Region including by state name is NOT the same' => [
            'filter'   => ['Texas'],
            'operator' => 'in',
            'expected' => false,
        ];

        yield 'Region including by state ID is NOT the same' => [
            'filter'   => ['555'],
            'operator' => 'in',
            'expected' => false,
        ];
    }

    public function testDWCContactStartWidth(): void
    {
        $this->filter[0]['operator'] = 'startsWith';
        $this->filter[0]['filter']   = 'my';

        $this->assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->lead['custom'] = 'another text';

        $this->assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testDWCContactWithRegex(): void
    {
        $this->lead['custom']        = '04249';
        $this->filter[0]['operator'] = 'regexp';
        $this->filter[0]['filter']   = '(13357|04249|20363)';

        $this->assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testDWCContactEndWidth(): void
    {
        $this->filter[0]['operator'] = 'endsWith';
        $this->filter[0]['filter']   = 'text';

        $this->assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->lead['custom'] = 'another words';

        $this->assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testDWCContactContains(): void
    {
        $this->filter[0]['operator'] = 'contains';
        $this->filter[0]['filter']   = 'custom';

        $this->assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->lead['custom'] = 'another words';

        $this->assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testMatchFilterForLeadWithNumberType(): void
    {
        $this->lead['custom'] = '10.5';

        $this->filter[0]['type']     = 'number';
        $this->filter[0]['operator'] = OperatorOptions::EQUAL_TO;
        $this->filter[0]['filter']   = '10.5';
        $this->assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->filter[0]['operator'] = OperatorOptions::GREATER_THAN_OR_EQUAL;
        $this->filter[0]['filter']   = '5.5';
        $this->filter                = [
            1 => [
                'display'  => null,
                'field'    => 'custom',
                'filter'   => '11.5',
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => OperatorOptions::LESS_THAN_OR_EQUAL,
                'type'     => 'number',
            ],
        ];
        $this->assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->filter[0]['operator'] = OperatorOptions::LESS_THAN_OR_EQUAL;
        $this->filter[0]['filter']   = '5.5';
        $this->filter                = [
            1 => [
                'display'  => null,
                'field'    => 'custom',
                'filter'   => '11.5',
                'glue'     => 'or',
                'object'   => 'lead',
                'operator' => OperatorOptions::GREATER_THAN_OR_EQUAL,
                'type'     => 'number',
            ],
        ];
        $this->assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    /**
     * @see https://github.com/acquia/mc-cs/pull/3135
     */
    #[DataProvider('numberNullAndZeroProvider')]
    public function testMatchFilterForLeadTraitNumberTreatsNullAndZeroLikeSegments(
        ?string $value,
        string $operator,
        ?string $filterValue,
        bool $expect,
    ): void {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'distance',
                'object'   => 'lead',
                'type'     => 'number',
                'filter'   => $filterValue,
                'display'  => null,
                'operator' => $operator,
            ],
        ];

        $lead = [
            'id'       => 1,
            'distance' => $value,
        ];

        $this->assertSame($expect, $this->matchFilterForLeadTrait->match($filters, $lead));
    }

    /**
     * @return iterable<string, array{0: ?string, 1: string, 2: ?string, 3: bool}>
     */
    public static function numberNullAndZeroProvider(): iterable
    {
        // NULL must never satisfy a comparison operator, the way SQL behaves for segments.
        yield 'null gte 0'     => [null, OperatorOptions::GREATER_THAN_OR_EQUAL, '0', false];
        yield 'null lte 10'    => [null, OperatorOptions::LESS_THAN_OR_EQUAL, '10', false];
        yield 'null gt 0'      => [null, OperatorOptions::GREATER_THAN, '0', false];
        yield 'null lt 10'     => [null, OperatorOptions::LESS_THAN, '10', false];

        // Zero is a real value and must still be compared numerically.
        yield 'zero gte 0'     => ['0', OperatorOptions::GREATER_THAN_OR_EQUAL, '0', true];
        yield 'zero lte 10'    => ['0', OperatorOptions::LESS_THAN_OR_EQUAL, '10', true];
        yield 'zero gt 0'      => ['0', OperatorOptions::GREATER_THAN, '0', false];
        yield 'five gte 0'     => ['5', OperatorOptions::GREATER_THAN_OR_EQUAL, '0', true];
        yield 'five lte 10'    => ['5', OperatorOptions::LESS_THAN_OR_EQUAL, '10', true];

        // NULL is not equal to 0, and the segment neq handler ORs in IS NULL.
        yield 'null eq 0'      => [null, OperatorOptions::EQUAL_TO, '0', false];
        yield 'null neq 0'     => [null, OperatorOptions::NOT_EQUAL_TO, '0', true];
        yield 'zero eq 0'      => ['0', OperatorOptions::EQUAL_TO, '0', true];
        yield 'zero neq 0'     => ['0', OperatorOptions::NOT_EQUAL_TO, '0', false];
        yield 'zero neq 5'     => ['0', OperatorOptions::NOT_EQUAL_TO, '5', true];

        // Zero is not "empty" for a numeric field.
        yield 'zero notEmpty'  => ['0', '!empty', null, true];
        yield 'zero empty'     => ['0', 'empty', null, false];
        yield 'null notEmpty'  => [null, '!empty', null, false];
        yield 'null empty'     => [null, 'empty', null, true];
        yield 'blank empty'    => ['', 'empty', null, true];
        yield 'blank notEmpty' => ['', '!empty', null, false];
    }

    #[DataProvider('dateMatchTestProvider')]
    public function testMatchFilterForLeadTraitForDate(?string $value, string $operator, bool $expect): void
    {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'date',
                'object'   => 'lead',
                'type'     => 'date',
                'filter'   => '2021-05-01',
                'display'  => null,
                'operator' => $operator,
            ],
        ];

        $lead = [
            'id'   => 1,
            'date' => $value,
        ];

        $this->assertSame($expect, $this->matchFilterForLeadTrait->match($filters, $lead));
    }

    /**
     * @return iterable<array{0: ?string, 1: string, 2: bool}>
     */
    public static function dateMatchTestProvider(): iterable
    {
        $date = '2021-05-01';

        yield [$date, '=', true];
        yield [$date, '!=', false];
        yield ['2020-02-02', '!=', true];
        yield [$date, '!=', false];
        yield [null, 'empty', true];
        yield [$date, 'empty', false];
        yield [$date, '!empty', true];
        yield [null, '!empty', false];
    }

    /**
     * @return mixed[]
     */
    public static function segmentMembershipFilterProvider(): iterable
    {
        yield 'Classic Segment Membership Filter With In Country' => [
            'leadlist',
            [
                'name'  => 'field_country',
                'type'  => 'country',
                'value' => 'Some country',
            ],
            [
                'operator' => OperatorOptions::IN,
                'value'    => 'Some country',
            ],
            true,
        ];
        yield 'Static Segment Membership Filter With In Country' => [
            'leadlist_static',
            [
                'name'  => 'field_country',
                'type'  => 'country',
                'value' => 'Some country',
            ],
            [
                'operator' => OperatorOptions::IN,
                'value'    => 'Some country',
            ],
            true,
        ];
        yield 'Classic Segment Membership Filter With Not In Country' => [
            'leadlist',
            [
                'name'  => 'field_country',
                'type'  => 'country',
                'value' => 'Some country',
            ],
            [
                'operator' => OperatorOptions::NOT_IN,
                'value'    => 'Some country',
            ],
            false,
        ];
        yield 'Static Segment Membership Filter With Not In Country' => [
            'leadlist_static',
            [
                'name'  => 'field_country',
                'type'  => 'country',
                'value' => 'Some country',
            ],
            [
                'operator' => OperatorOptions::NOT_IN,
                'value'    => 'Some country',
            ],
            false,
        ];
    }

    /**
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $filterDetails
     */
    #[DataProvider('segmentMembershipFilterProvider')]
    public function testIsContactSegmentRelationshipValidEmpty(string $leadListFilterField, array $fieldDetails, array $filterDetails, bool $expected): void
    {
        $lead = [
            'id'                  => 1,
            $fieldDetails['name'] => $fieldDetails['value'],
        ];
        $segmentId  = 1;
        $operator   = OperatorOptions::EMPTY;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects($this->once())
            ->method('isNotContactInAnySegment')
            ->with($lead['id'])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => $leadListFilterField,
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
            1 => [
                'display'  => null,
                'field'    => $fieldDetails['name'],
                'filter'   => $filterDetails['value'],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $filterDetails['operator'],
                'type'     => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->assertSame($expected, $trait->match($filter, $lead));
    }

    /**
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $filterDetails
     */
    #[DataProvider('segmentMembershipFilterProvider')]
    public function testIsContactSegmentRelationshipValidNotEmpty(string $leadListFilterField, array $fieldDetails, array $filterDetails, bool $expected): void
    {
        $lead = [
            'id'                  => 1,
            $fieldDetails['name'] => $fieldDetails['value'],
        ];
        $segmentId  = 1;
        $operator   = OperatorOptions::NOT_EMPTY;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects($this->once())
            ->method('isContactInAnySegment')
            ->with($lead['id'])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => $leadListFilterField,
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
            1 => [
                'display'  => null,
                'field'    => $fieldDetails['name'],
                'filter'   => $filterDetails['value'],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $filterDetails['operator'],
                'type'     => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->assertSame($expected, $trait->match($filter, $lead));
    }

    /**
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $filterDetails
     */
    #[DataProvider('segmentMembershipFilterProvider')]
    public function testIsContactSegmentRelationshipValidIn(string $leadListFilterField, array $fieldDetails, array $filterDetails, bool $expected): void
    {
        $lead = [
            'id'                  => 1,
            $fieldDetails['name'] => $fieldDetails['value'],
        ];
        $segmentId  = 1;
        $operator   = OperatorOptions::IN;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects($this->once())
            ->method('isContactInSegments')
            ->with($lead['id'], [0 => $segmentId])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => $leadListFilterField,
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
            1 => [
                'display'  => null,
                'field'    => $fieldDetails['name'],
                'filter'   => $filterDetails['value'],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $filterDetails['operator'],
                'type'     => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->assertSame($expected, $trait->match($filter, $lead));
    }

    /**
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $filterDetails
     */
    #[DataProvider('segmentMembershipFilterProvider')]
    public function testIsContactSegmentRelationshipValidNotIn(string $leadListFilterField, array $fieldDetails, array $filterDetails, bool $expected): void
    {
        $lead = [
            'id'                  => 1,
            $fieldDetails['name'] => $fieldDetails['value'],
        ];
        $segmentId  = 1;
        $operator   = OperatorOptions::NOT_IN;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects($this->once())
            ->method('isNotContactInSegments')
            ->with($lead['id'], [0 => $segmentId])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => $leadListFilterField,
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
            1 => [
                'display'  => null,
                'field'    => $fieldDetails['name'],
                'filter'   => $filterDetails['value'],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $filterDetails['operator'],
                'type'     => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->assertSame($expected, $trait->match($filter, $lead));
    }

    /**
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $filterDetails
     */
    #[DataProvider('segmentMembershipFilterProvider')]
    public function testIsContactSegmentRelationshipValidInvalidOperator(string $leadListFilterField, array $fieldDetails, array $filterDetails, bool $expected): void
    {
        $lead = [
            'id'                  => 1,
            $fieldDetails['name'] => $fieldDetails['value'],
        ];
        $segmentId  = 1;
        $operator   = 'invalid';

        $segmentRepository = $this->createStub(LeadListRepository::class);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => $leadListFilterField,
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
            1 => [
                'display'  => null,
                'field'    => $fieldDetails['name'],
                'filter'   => $filterDetails['value'],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $filterDetails['operator'],
                'type'     => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->expectException(\InvalidArgumentException::class);

        $trait->match($filter, $lead);
    }

    /**
     * @param array<string,string> $fieldDetails
     * @param array<string,string> $filterDetails
     */
    #[DataProvider('dataForInNotInOperatorFilter')]
    public function testCheckLeadValueIsInFilter(array $fieldDetails, array $filterDetails, bool $expected): void
    {
        $lead = [
            'id'                  => 1,
            $fieldDetails['name'] => $fieldDetails['value'],
        ];

        $filter = [
            0 => [
                'display'  => null,
                'field'    => $fieldDetails['name'],
                'filter'   => $filterDetails['value'],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $filterDetails['operator'],
                'type'     => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();

        $this->assertSame($expected, $trait->match($filter, $lead));
    }

    /**
     * @return mixed[]
     */
    public static function dataForInNotInOperatorFilter(): iterable
    {
        // field details, filter details, expected.
        yield [
            [
                'name'  => 'field_select',
                'type'  => 'select',
                'value' => 'one',
            ],
            [
                'operator'  => OperatorOptions::INCLUDING_ANY,
                'value'     => 'one',
            ],
            true,
        ];
        yield [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two',
            ],
            [
                'operator'  => OperatorOptions::EXCLUDING_ANY,
                'value'     => 'three',
            ],
            true,
        ];
        yield [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two|three',
            ],
            [
                'operator'  => OperatorOptions::EXCLUDING_ANY,
                'value'     => 'one|four',
            ],
            false,
        ];
        yield [
            [
                'name'  => 'field_country',
                'type'  => 'country',
                'value' => 'Some country',
            ],
            [
                'operator'  => OperatorOptions::INCLUDING_ANY,
                'value'     => 'Some country',
            ],
            true,
        ];
        yield [
            [
                'name'  => 'field_country',
                'type'  => 'country',
                'value' => 'Some country',
            ],
            [
                'operator'  => OperatorOptions::INCLUDING_ANY,
                'value'     => 'Some other country',
            ],
            false,
        ];
        yield 'Excluding all, none of the values matched.' => [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two',
            ],
            [
                'operator'  => OperatorOptions::EXCLUDING_ALL,
                'value'     => 'three|four',
            ],
            true,
        ];
        yield 'Excluding all, some of the values matched.' => [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two|three',
            ],
            [
                'operator'  => OperatorOptions::EXCLUDING_ALL,
                'value'     => 'one|four',
            ],
            true,
        ];
        yield 'Excluding all, all of the values matched.' => [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two|three',
            ],
            [
                'operator'  => OperatorOptions::EXCLUDING_ALL,
                'value'     => 'one|three',
            ],
            false,
        ];
        yield 'Including all, none of the values matched.' => [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two',
            ],
            [
                'operator'  => OperatorOptions::INCLUDING_ALL,
                'value'     => 'three|four',
            ],
            false,
        ];
        yield 'Including all, some of the values matched.' => [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two|three',
            ],
            [
                'operator'  => OperatorOptions::INCLUDING_ALL,
                'value'     => 'one|four',
            ],
            false,
        ];
        yield 'Including all, all of the values matched.' => [
            [
                'name'  => 'field_multiselect',
                'type'  => 'multiselect',
                'value' => 'one|two|three',
            ],
            [
                'operator'  => OperatorOptions::INCLUDING_ALL,
                'value'     => 'one|three',
            ],
            true,
        ];
    }

    public function testMatchFilterForLeadTraitEmptyCompany(): void
    {
        $lead = [
            'id'        => 1,
            'firstname' => 'Test',
            'companies' => [],
        ];

        $filters = [
            0 => [
                'glue'      => 'and',
                'field'     => 'companycity',
                'object'    => 'company',
                'type'      => 'text',
                'filter'    => 'New York',
                'display'   => null,
                'operator'  => '=',
            ],
        ];

        try {
            $this->assertEquals(false, $this->matchFilterForLeadTrait->match($filters, $lead));
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testIsContactSegmentRelationshipValidInAll(): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = OperatorOptions::INCLUDING_ALL;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects($this->once())
            ->method('isContactInAllSegments')
            ->with($lead['id'], [0 => $segmentId, 1 => 2])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => 'leadlist',
                'filter'  => [
                    0 => $segmentId,
                    1 => 2,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->assertTrue($trait->match($filter, $lead));
    }

    public function testIsContactSegmentRelationshipValidNotInAll(): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = OperatorOptions::EXCLUDING_ALL;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects($this->once())
            ->method('isNotContactInAllSegments')
            ->with($lead['id'], [0 => $segmentId, 1 => 2])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => 'leadlist',
                'filter'  => [
                    0 => $segmentId,
                    1 => 2,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->assertTrue($trait->match($filter, $lead));
    }
}

final class MatchFilterForLeadTraitTestable
{
    use MatchFilterForLeadTrait;

    private LeadListRepository $segmentRepository;

    public function setRepository(LeadListRepository $segmentRepository): void
    {
        $this->segmentRepository = $segmentRepository;
    }

    /**
     * @param array<int, array<string, mixed>> $filter
     * @param array<string, mixed>             $lead
     */
    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
