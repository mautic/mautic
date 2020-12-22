<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadListRepository;
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
