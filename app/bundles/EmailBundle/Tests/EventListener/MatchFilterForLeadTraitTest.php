<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;

class MatchFilterForLeadTraitTest extends \PHPUnit\Framework\TestCase
{
    use MatchFilterForLeadTrait;

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

        $this->assertEquals($expect, $this->matchFilterForLead($filters, $lead));
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
