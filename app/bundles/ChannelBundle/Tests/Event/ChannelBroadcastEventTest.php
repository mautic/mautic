<?php

namespace Mautic\ChannelBundle\Tests\Event;

use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ChannelBroadcastEventTest extends TestCase
{
    private string $channel;

    private int $channelId;

    private OutputInterface $output;

    protected function setUp(): void
    {
        $this->channel   = 'email';
        $this->channelId = 1;
        $this->output    = new BufferedOutput();
    }

    public function testConstructorAndGetters(): void
    {
        $event = new ChannelBroadcastEvent($this->channel, $this->channelId, $this->output);

        $this->assertSame($this->channel, $event->getChannel());
        $this->assertSame($this->channelId, $event->getId());
        $this->assertSame($this->output, $event->getOutput());
    }

    public function testResults(): void
    {
        $event                  = new ChannelBroadcastEvent($this->channel, $this->channelId, $this->output);
        $successCount           = 10;
        $failedCount            = 2;
        $failedRecipientsByList = ['list1' => ['user1@example.com', 'user2@example.com']];

        $event->setResults($this->channel, $successCount, $failedCount, $failedRecipientsByList);

        $this->assertSame([
            $this->channel => [
                'success'                => $successCount,
                'failed'                 => $failedCount,
                'failedRecipientsByList' => $failedRecipientsByList,
            ],
        ], $event->getResults());
    }

    public function testCheckContext(): void
    {
        $event = new ChannelBroadcastEvent($this->channel, $this->channelId, $this->output);

        $this->assertTrue($event->checkContext('email'));
        $this->assertFalse($event->checkContext('sms'));
    }
}
