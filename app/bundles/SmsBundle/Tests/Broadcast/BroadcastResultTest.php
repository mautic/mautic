<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Broadcast;

use Mautic\SmsBundle\Broadcast\BroadcastResult;
use PHPUnit\Framework\TestCase;

final class BroadcastResultTest extends TestCase
{
    public function testItSeparatesSubmittedScheduledAndFailedResults(): void
    {
        $result = new BroadcastResult();

        $result->process([
            10 => [
                'sent'   => true,
                'status' => 'mautic.sms.timeline.status.delivered',
            ],
            20 => [
                'sent'   => false,
                'status' => 'mautic.sms.timeline.status.scheduled',
            ],
            30 => [
                'sent'   => false,
                'status' => 'mautic.sms.campaign.failed.missing_number',
            ],
        ]);
        $result->setRemainingCount(4);

        $this->assertSame(3, $result->getProcessedCount());
        $this->assertSame(1, $result->getSubmittedCount());
        $this->assertSame(1, $result->getSentCount());
        $this->assertSame(1, $result->getScheduledCount());
        $this->assertSame(2, $result->getSuccessfulCount());
        $this->assertSame(1, $result->getFailedCount());
        $this->assertSame(4, $result->getRemainingCount());
        $this->assertSame([30 => 'mautic.sms.campaign.failed.missing_number'], $result->getFailedContacts());
    }

    public function testItAccountsForMissingSendResultsAsFailures(): void
    {
        $result = new BroadcastResult();

        $result->process([
            10 => ['sent' => true],
            20 => ['sent' => false],
        ], 3);

        $this->assertSame(3, $result->getProcessedCount());
        $this->assertSame(1, $result->getSubmittedCount());
        $this->assertSame(2, $result->getFailedCount());
        $this->assertSame([20 => 'mautic.sms.timeline.event.failed'], $result->getFailedContacts());
    }

    public function testItAggregatesBatchAndExecutionResults(): void
    {
        $firstBatch = new BroadcastResult();
        $firstBatch->process([
            10 => ['sent' => true],
            20 => [
                'sent'   => false,
                'status' => 'mautic.sms.timeline.status.scheduled',
            ],
        ]);
        $firstBatch->setRemainingCount(2);

        $secondBatch = new BroadcastResult();
        $secondBatch->process([
            30 => [
                'sent'   => false,
                'status' => 'failed',
            ],
        ]);
        $secondBatch->executionFailed();
        $secondBatch->setRemainingCount(1);

        $result = new BroadcastResult();
        $result->merge($firstBatch);
        $result->merge($secondBatch);

        $this->assertSame(3, $result->getProcessedCount());
        $this->assertSame(1, $result->getSubmittedCount());
        $this->assertSame(1, $result->getScheduledCount());
        $this->assertSame(2, $result->getFailedCount());
        $this->assertSame(1, $result->getExecutionFailureCount());
        $this->assertSame(1, $result->getRemainingCount());
        $this->assertSame([30 => 'failed'], $result->getFailedContacts());
    }
}
