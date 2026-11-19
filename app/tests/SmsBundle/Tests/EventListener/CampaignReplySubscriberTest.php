<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\EventListener\CampaignReplySubscriber;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\TestCase;

final class CampaignReplySubscriberTest extends TestCase
{
    public function testMatchingReplySetsChannelAndIsApplicable(): void
    {
        $decisionEvent = $this->createDecisionEvent('stop*', 'Stop sending');

        $this->createSubscriber()->onCampaignReply($decisionEvent);

        $this->assertTrue($decisionEvent->wasDecisionApplicable());
        $this->assertSame('sms', $decisionEvent->getLog()->getChannel());
    }

    public function testNotMatchingReplyIsNotApplicable(): void
    {
        $decisionEvent = $this->createDecisionEvent('stop*', 'Hello');

        $this->createSubscriber()->onCampaignReply($decisionEvent);

        $this->assertFalse($decisionEvent->wasDecisionApplicable());
        $this->assertNull($decisionEvent->getLog()->getChannel());
    }

    private function createDecisionEvent(string $pattern, string $message): DecisionEvent
    {
        $event = new Event();
        $event->setCampaign(new Campaign());
        $event->setProperties(['pattern' => $pattern]);

        $leadEventLog = new LeadEventLog();
        $leadEventLog->setEvent($event);

        return new DecisionEvent(
            new DecisionAccessor([]),
            $leadEventLog,
            new ReplyEvent(new Lead(), $message)
        );
    }

    private function createSubscriber(): CampaignReplySubscriber
    {
        return new CampaignReplySubscriber(
            $this->createStub(TransportChain::class),
            $this->createStub(RealTimeExecutioner::class)
        );
    }
}
