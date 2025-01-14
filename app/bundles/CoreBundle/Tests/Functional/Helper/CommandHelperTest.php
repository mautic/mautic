<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Helper\CommandHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class CommandHelperTest extends MauticMysqlTestCase
{
    /**
     * @var CommandHelper
     */
    private $commandHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commandHelper = $this->getContainer()->get('mautic.helper.command');
    }

    public function testRunCommandWithParam(): void
    {
        $response = $this->commandHelper->runCommand('help', ['--version']);
        Assert::assertSame(0, $response->getStatusCode());
        Assert::assertStringContainsString('(env: test, debug: false)', $response->getMessage());
    }

    public function testRunCommandWithoutParam(): void
    {
        $response = $this->commandHelper->runCommand('list');
        Assert::assertSame(0, $response->getStatusCode());
        Assert::assertStringContainsString('doctrine:database:create', $response->getMessage());
    }
}
