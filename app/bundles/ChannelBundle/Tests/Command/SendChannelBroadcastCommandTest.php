<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class SendChannelBroadcastCommandTest extends MauticMysqlTestCase
{
    public function testBroadcastCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send');
        Assert::assertSame(0, $commandTester->getStatusCode());
    }

    public function testBroadcastCommandWithLimit(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:broadcasts:send', ['--limit' => 1]);
        Assert::assertSame(0, $commandTester->getStatusCode());
    }
}
