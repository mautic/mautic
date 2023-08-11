<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class GenerateProductionAssetsCommandTest extends MauticMysqlTestCase
{
    public function testCkeditorFileNotExist(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:assets:generate');
        
        $this->assertStringContainsString('Production assets have been regenerated.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
