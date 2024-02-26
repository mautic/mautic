<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class CampaignTest extends TestCase
{
    public function testGetEventsByType(): void
    {
        $campaign = $this->addSomeEvents(new Campaign());

        Assert::assertCount(2, $campaign->getEventsByType(Event::TYPE_DECISION));
        Assert::assertCount(1, $campaign->getEventsByType(Event::TYPE_ACTION));
        Assert::assertCount(1, $campaign->getEventsByType(Event::TYPE_CONDITION));
    }

    private function addSomeEvents(Campaign $campaign): Campaign
    {
        $decisionA = new EventFake(1);
        $decisionA->setName('Decision A');
        $decisionA->setEventType(Event::TYPE_DECISION);

        $action = new EventFake(2);
        $action->setName('Action A');
        $action->setEventType(Event::TYPE_ACTION);

        $condition = new EventFake(3);
        $condition->setName('Condition A');
        $condition->setEventType(Event::TYPE_CONDITION);

        $decisionB = new EventFake(4);
        $decisionB->setName('Decision B');
        $decisionB->setEventType(Event::TYPE_DECISION);

        $campaign->addEvent($decisionA->getId(), $decisionA);
        $campaign->addEvent($action->getId(), $action);
        $campaign->addEvent($condition->getId(), $condition);
        $campaign->addEvent($decisionB->getId(), $decisionB);

        return $campaign;
    }
}
