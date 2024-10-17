<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Helper\EventHelper;
use PHPUnit\Framework\TestCase;

class EventHelperTest extends TestCase
{
    public function testEngagePointAction(): void
    {
        $lead = new Lead();

        // Define the action array
        $action = ['id' => 1, 'type' => 'helloworld.action.custom_action', 'name' => 'My custom point action', 'properties' => [], 'points' => 50];

        $points = EventHelper::engagePointAction($lead, $action);
        $this->assertEquals(50, $points);

        $points = EventHelper::engagePointAction($lead, $action);
        $this->assertEquals(0, $points, 'Second call should return 0 points because the action is already initiated for this lead and type and session.');
    }
}
