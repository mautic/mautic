<?php

namespace Mautic\CampaignBundle\Tests\EventCollector\Builder;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Builder\ConnectionBuilder;

class ConnectionBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayIsBuiltAsItsUsedInJsPlumb()
    {
        $eventsArray = [
            Event::TYPE_ACTION   => [
                'action1' => [
                    'connectionRestrictions' => [
                        'anchor' => ['decision1.inaction'],
                        'source' => [
                            'decision' => [
                                'decision1',
                            ],
                        ],
                    ],
                ],
                'action2' => [
                    // BC from way back
                    'associatedDecisions' => [
                        'decision1',
                    ],
                ],
                'action3' => [
                    // BC from way back
                    'anchorRestrictions' => [
                        'decision2.top',
                    ],
                ],
            ],
            Event::TYPE_DECISION => [
                'decision1' => [
                    'connectionRestrictions' => ['source' => ['action' => ['action1']]],
                ],
                'decision2' => [
                    // BC From way back
                    'associatedActions' => [
                        'some.decision',
                    ],
                ],
            ],
        ];

        $results = ConnectionBuilder::buildRestrictionsArray($eventsArray);

        $expected = [
            'anchor'    => [
                'decision1' => [
                    'action1' => ['inaction'],
                ],
                'action3'   => [
                    'decision2' => ['top'],
                ],
            ],
            'action1'   => [
                'source' => [
                    'action'   => [],
                    'decision' => ['decision1'],
                ],
                'target' => [
                    'action'   => [],
                    'decision' => [],
                ],
            ],
            'action2'   => [
                'source' => [
                    'action'   => [],
                    'decision' => ['decision1'],
                ],
                'target' => [
                    'action'   => [],
                    'decision' => [],
                ],
            ],
            'action3'   => [
                'source' => [
                    'action'   => [],
                    'decision' => [],
                ],
                'target' => [
                    'action'   => [],
                    'decision' => [],
                ],
            ],
            'decision1' => [
                'source' => [
                    'action'   => ['action1'],
                    'decision' => [],
                ],
                'target' => [
                    'action'   => [],
                    'decision' => [],
                ],
            ],
            'decision2' => [
                'source' => [
                    'action'   => [],
                    'decision' => [],
                ],
                'target' => [
                    'action'   => ['some.decision'],
                    'decision' => [],
                ],
            ],
        ];

        $this->assertEquals($expected, $results);
    }
}
