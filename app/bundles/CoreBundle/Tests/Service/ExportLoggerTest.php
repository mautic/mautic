<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\ExportLogger;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ExportLoggerTest extends TestCase
{
    private CoreParametersHelper $coreParametersHelper;
    private ExportLogger $exportLogger;
    private User $user;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParametersHelper->method('get')->willReturnMap([
            ['log_exports_path', '%kernel.root_dir%/../var/logs/exports'],
            ['log_file_exports_name', 'exports_prod.php'],
            ['max_log_exports_files', 7],
        ]);

        $this->exportLogger = new ExportLogger($this->coreParametersHelper);

        $this->user = $this->createMock(User::class);
        $this->user->method('getId')->willReturn(1);
        $this->user->method('getEmail')->willReturn('user@example.com');
    }

    public function testLoggerInitialization(): void
    {
        $this->assertInstanceOf(Logger::class, $this->exportLogger->getLogger());
        $handlers = $this->exportLogger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);
    }

    public function testLoggerInfo(): void
    {
        $logFilePath = '/var/www/html/var/logs/exports/exports_prod-'.(new \DateTime())->format('Y-m-d').'.php';

        if (file_exists($logFilePath)) {
            unlink($logFilePath);
        }

        $this->exportLogger->loggerInfo($this->user, ExportLogger::LEAD_EXPORT, ['param1' => 'value1']);

        $this->assertFileExists($logFilePath);
        $logContent = file_get_contents($logFilePath);
        $this->assertStringContainsString('User #', $logContent);

        unlink($logFilePath);
    }
}
