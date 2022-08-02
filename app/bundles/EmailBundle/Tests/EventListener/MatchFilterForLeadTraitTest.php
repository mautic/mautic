<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
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
}

class MatchFilterForLeadTraitTestable
{
    use MatchFilterForLeadTrait;

    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
