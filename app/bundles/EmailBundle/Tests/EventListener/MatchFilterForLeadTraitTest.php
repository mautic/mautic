<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\TestCase;

class MatchFilterForLeadTraitTest extends TestCase
{
    private $lead = [
        'id'     => 1,
        'custom' => 'my custom text',
    ];

    private $filter = [
        0 => [
            'display' => null,
            'field'   => 'custom',
            'glue'    => 'and',
            'object'  => 'lead',
            'type'    => 'text',
        ],
    ];

    /**
     * @var MatchFilterForLeadTraitTestable
     */
    private $matchFilterForLeadTrait;

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

    /**
     * @dataProvider dateMatchTestProvider
     */
    public function testMatchFilterForLeadTraitForDate(?string $value, string $operator, bool $expect)
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

    public function dateMatchTestProvider(): iterable
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
            'id'                    => 1,
            $fieldDetails['name']   => $fieldDetails['value'],
        ];

        $filter = [
            0 => [
                'display'   => null,
                'field'     => $fieldDetails['name'],
                'filter'    => $filterDetails['value'],
                'glue'      => 'and',
                'object'    => 'lead',
                'operator'  => $filterDetails['operator'],
                'type'      => $fieldDetails['type'],
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();

        $this->assertSame($expected, $trait->match($filter, $lead));
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
}

class MatchFilterForLeadTraitTestable
{
    use MatchFilterForLeadTrait;

    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
