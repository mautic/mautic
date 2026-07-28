<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Helper\CommandHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class CommandHelperTest extends MauticMysqlTestCase
{
    private CommandHelper $commandHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commandHelper = static::getContainer()->get(CommandHelper::class);
    }

    public function testRunCommandWithParam(): void
    {
        $response = $this->commandHelper->runCommand('help', ['--version']);
        $this->assertSame(0, $response->getStatusCode());
        $this->assertStringContainsString('(env: test, debug: false)', $response->getMessage());
    }

    public function testRunCommandWithoutParam(): void
    {
        $response = $this->commandHelper->runCommand('list');
        $this->assertSame(0, $response->getStatusCode());
        $this->assertStringContainsString('doctrine:database:create', $response->getMessage());
    }
}
