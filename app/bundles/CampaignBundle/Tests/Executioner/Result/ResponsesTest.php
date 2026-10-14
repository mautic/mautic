<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Result;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Result\Responses;
use Mautic\CoreBundle\Test\ReflectionHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class ResponsesTest extends \PHPUnit\Framework\TestCase
{
    public function testExtractingResponsesFromLog(): void
    {
        $campaign   = new Campaign();

        $actionEvent = new Event();
        $actionEvent->setCampaign($campaign);
        $actionEvent->setEventType(Event::TYPE_ACTION);
        $actionEvent->setType('actionEvent');
        ReflectionHelper::setEntityId($actionEvent, 1);

        // BC should set response as just test
        $actionLog = new LeadEventLog();
        $actionLog->setEvent($actionEvent);
        $actionLog->setMetadata(['timeline' => 'test']);

        $action2Event = new Event();
        $action2Event->setCampaign($campaign);
        $action2Event->setEventType(Event::TYPE_ACTION);
        $action2Event->setType('action2Event');
        ReflectionHelper::setEntityId($action2Event, 2);

        // Response should be full array
        $action2Log = new LeadEventLog();
        $action2Log->setEvent($action2Event);
        $action2Log->setMetadata(['timeline' => 'test', 'something' => 'else']);

        // Response should be full array
        $conditionEvent = new Event();
        $conditionEvent->setCampaign($campaign);
        $conditionEvent->setEventType(Event::TYPE_CONDITION);
        $conditionEvent->setType('conditionEvent');
        ReflectionHelper::setEntityId($conditionEvent, 3);

        $conditionLog = new LeadEventLog();
        $conditionLog->setEvent($conditionEvent);
        $conditionLog->setMetadata(['something' => 'else']);

        /** @var Collection<int, LeadEventLog> $logs */
        $logs = new ArrayCollection([$actionLog, $action2Log, $conditionLog]);

        $responses = new Responses();
        $responses->setFromLogs($logs);

        $actions = [
            'actionEvent'  => [
                1 => 'test',
            ],
            'action2Event' => [
                2 => [
                    'timeline'  => 'test',
                    'something' => 'else',
                ],
            ],
        ];

        $conditions = [
            'conditionEvent' => [
                3 => [
                    'something' => 'else',
                ],
            ],
        ];

        $this->assertEquals($actions, $responses->getActionResponses());
        $this->assertEquals($conditions, $responses->getConditionResponses());
    }
}
