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
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\TestCase;

class MatchFilterForLeadTraitTest extends TestCase
{
    public function testDWCContactCountryIn(): void
    {
        $country  = 'Czech Republic';
        $lead     = [
            'id'      => 1,
            'country' => $country,
        ];
        $operator = OperatorOptions::IN;

        $filter = [
            0 => [
                'display' => null,
                'field'   => 'country',
                'filter'  => [
                    0 => $country,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'country',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();

        self::assertTrue($trait->match($filter, $lead));

        $lead['country'] = 'someOtherCountry';

        self::assertFalse($trait->match($filter, $lead));
    }

    public function testDWCContactCountryNotIn(): void
    {
        $country  = 'Czech Republic';
        $lead     = [
            'id'      => 1,
            'country' => 'someOtherCountry',
        ];
        $operator = OperatorOptions::NOT_IN;

        $filter = [
            0 => [
                'display' => null,
                'field'   => 'country',
                'filter'  => [
                    0 => $country,
                ],
                'glue'     => 'and',
                'object'   => 'lead',
                'operator' => $operator,
                'type'     => 'country',
            ],
        ];

        $trait = new MatchFilterForLeadTraitTestable();

        self::assertTrue($trait->match($filter, $lead));

        $lead['country'] = $country;

        self::assertFalse($trait->match($filter, $lead));
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
