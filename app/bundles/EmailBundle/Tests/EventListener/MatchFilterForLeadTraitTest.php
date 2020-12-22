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
use Mautic\LeadBundle\Entity\LeadListRepository;
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

    public function testIsContactSegmentRelationshipValidEmpty(): void
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

        self::assertSame(true, $trait->match($filter, $lead));
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

        self::assertSame(true, $trait->match($filter, $lead));
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

        self::assertSame(true, $trait->match($filter, $lead));
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

        self::assertSame(true, $trait->match($filter, $lead));
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

    public function setRepository($segmentRepository): void
    {
        $this->segmentRepository = $segmentRepository;
    }

    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
