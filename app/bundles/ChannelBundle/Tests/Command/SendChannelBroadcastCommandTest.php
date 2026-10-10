<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class SendChannelBroadcastCommandTest extends MauticMysqlTestCase
{
    public function testBroadcastCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send');
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testBroadcastCommandWithLimit(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send', ['--limit' => 1]);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
