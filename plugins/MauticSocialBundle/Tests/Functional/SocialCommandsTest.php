<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class SocialCommandsTest extends MauticMysqlTestCase
{
    public function testSocialMonitoringCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:social:monitoring');

        Assert::assertSame(0, $commandTester->getStatusCode());
        Assert::assertSame("No published monitors found. Make sure the id you supplied is published\n", $commandTester->getDisplay());
    }

    public function testTwitterHashtagsCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('social:monitor:twitter:hashtags');

        Assert::assertSame(1, $commandTester->getStatusCode());
        Assert::assertSame("Twitter plugin not published!\n", $commandTester->getDisplay());
    }

    public function testTwitterMentionsCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('social:monitor:twitter:mentions');

        Assert::assertSame(1, $commandTester->getStatusCode());
        Assert::assertSame("Twitter plugin not published!\n", $commandTester->getDisplay());
    }
}
