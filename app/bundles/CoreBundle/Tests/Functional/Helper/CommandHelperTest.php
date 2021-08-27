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
        $this->commandHelper = $this->container->get('mautic.helper.command');
    }

    public function testRunCommandWithParam(): void
    {
        $outPut = $this->commandHelper->runCommand('help', ['--version']);
        Assert::assertStringContainsString('(kernel: app, env: test, debug: false)', $outPut);
    }

    public function testRunCommandWithoutParam(): void
    {
        $outPut = $this->commandHelper->runCommand('list');
        Assert::assertStringContainsString('doctrine:database:create', $outPut);
    }
}
