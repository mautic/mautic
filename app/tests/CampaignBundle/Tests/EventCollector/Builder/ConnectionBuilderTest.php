<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventCollector\Builder;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Builder\ConnectionBuilder;

final class ConnectionBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayIsBuiltAsItsUsedInJsPlumb(): void
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
            ],
            Event::TYPE_DECISION => [
                'decision1' => [
                    'connectionRestrictions' => ['source' => ['action' => ['action1']]],
                ],
            ],
        ];

        $results = ConnectionBuilder::buildRestrictionsArray($eventsArray);

        $expected = [
            'anchor'    => [
                'decision1' => [
                    'action1' => ['inaction'],
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
        ];

        $this->assertSame($expected, $results);
    }
}
