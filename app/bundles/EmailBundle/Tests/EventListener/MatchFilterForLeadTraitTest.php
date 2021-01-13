<?php

declare(strict_types=1);

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
}

class MatchFilterForLeadTraitTestable
{
    use MatchFilterForLeadTrait;

    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
