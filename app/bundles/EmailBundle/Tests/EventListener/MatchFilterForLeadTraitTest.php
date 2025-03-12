<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\TestCase;

class MatchFilterForLeadTraitTest extends TestCase
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
    private $filter = [
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
    }

    public function testDWCContactStartWidth(): void
    {
        $this->filter[0]['operator'] = 'startsWith';
        $this->filter[0]['filter']   = 'my';

        self::assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->lead['custom'] = 'another text';

        self::assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testDWCContactWithRegex(): void
    {
        $this->lead['custom']        = '04249';
        $this->filter[0]['operator'] = 'regexp';
        $this->filter[0]['filter']   = '(13357|04249|20363)';

        self::assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testDWCContactEndWidth(): void
    {
        $this->filter[0]['operator'] = 'endsWith';
        $this->filter[0]['filter']   = 'text';

        self::assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->lead['custom'] = 'another words';

        self::assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
    }

    public function testDWCContactContains(): void
    {
        $this->filter[0]['operator'] = 'contains';
        $this->filter[0]['filter']   = 'custom';

        self::assertTrue($this->matchFilterForLeadTrait->match($this->filter, $this->lead));

        $this->lead['custom'] = 'another words';

        self::assertFalse($this->matchFilterForLeadTrait->match($this->filter, $this->lead));
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
     * @dataProvider dateMatchTestProvider
     */
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

        $this->assertEquals($expect, $this->matchFilterForLeadTrait->match($filters, $lead));
    }

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
     * @dataProvider dataForInNotInOperatorFilter
     *
     * @param array<string,string> $fieldDetails
     * @param array<string,string> $filterDetails
     */
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
     * @return iterable<string, string[]>
     */
    public function segmentMembershipFilterProvider(): iterable
    {
        yield 'Classic Segment Membership Filter' => ['leadlist'];
        yield 'Static Segment Membership Filter' => ['leadlist_static'];
    }

    /**
     * @dataProvider segmentMembershipFilterProvider
     */
    public function testIsContactSegmentRelationshipValidEmpty(string $filterField): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = OperatorOptions::EMPTY;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects(self::once())
            ->method('isNotContactInAnySegment')
            ->with($lead['id'])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => $filterField,
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        self::assertTrue($trait->match($filter, $lead));
    }

    /**
     * @return mixed[]
     */
    public function dataForInNotInOperatorFilter(): iterable
    {
        // field details, filter details, expected.
        yield [
            [
                'name'  => 'field_select',
                'type'  => 'select',
                'value' => 'one',
            ],
            [
                'operator'  => OperatorOptions::IN,
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
                'operator'  => OperatorOptions::NOT_IN,
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
                'operator'  => OperatorOptions::NOT_IN,
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
                'operator'  => OperatorOptions::IN,
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
                'operator'  => OperatorOptions::IN,
                'value'     => 'Some other country',
            ],
            false,
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

    public function testIsContactSegmentRelationshipValidNotEmpty(): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = OperatorOptions::NOT_EMPTY;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects(self::once())
            ->method('isContactInAnySegment')
            ->with($lead['id'])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => 'leadlist',
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        self::assertTrue($trait->match($filter, $lead));
    }

    public function testIsContactSegmentRelationshipValidIn(): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = OperatorOptions::IN;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects(self::once())
            ->method('isContactInSegments')
            ->with($lead['id'], [0 => $segmentId])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => 'leadlist',
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        self::assertTrue($trait->match($filter, $lead));
    }

    public function testIsContactSegmentRelationshipValidNotIn(): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = OperatorOptions::NOT_IN;

        $segmentRepository = $this->createMock(LeadListRepository::class);
        $segmentRepository->expects(self::once())
            ->method('isNotContactInSegments')
            ->with($lead['id'], [0 => $segmentId])
            ->willReturn(true);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => 'leadlist',
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        self::assertTrue($trait->match($filter, $lead));
    }

    public function testIsContactSegmentRelationshipValidInvalidOperator(): void
    {
        $lead['id'] = 1;
        $segmentId  = 1;
        $operator   = 'invalid';

        $segmentRepository = $this->createMock(LeadListRepository::class);

        $filter = [
            0 => [
                'display' => 'Segment Membership',
                'field'   => 'leadlist',
                'filter'  => [
                    0 => $segmentId,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'leadlist',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();
        $trait->setRepository($segmentRepository);

        $this->expectException(\InvalidArgumentException::class);

        $trait->match($filter, $lead);
    }
}

class MatchFilterForLeadTraitTestable
{
    use MatchFilterForLeadTrait;

    private LeadListRepository $segmentRepository;

    public function setRepository(LeadListRepository $segmentRepository): void
    {
        $this->segmentRepository = $segmentRepository;
    }

    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
