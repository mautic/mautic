<?php

namespace Mautic\CoreBundle\Tests\Unit\Monolog\Handler;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Monolog\Handler\FileLogHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FileLogHandlerTest extends TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    public function testPropertiesAreSetFromCoreParametersHelperWhenDebugModeEnabled(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                function ($key) {
                    switch ($key) {
                        case 'log_path':
                            return '/var/logs';
                        case 'log_file_name':
                            return 'mautic_test.php';
                        case 'max_log_files':
                            return 7;
                        case 'debug':
                            return true;
                    }
                }
            );

        $formatterStub = $this->createStub(FormatterInterface::class);

        $handler = new FileLogHandler($this->coreParametersHelper, $formatterStub);
        $this->assertSame(Level::Debug, $handler->getLevel());
        $this->assertSame(spl_object_id($formatterStub), spl_object_id($handler->getFormatter()));

        $filename = $this->getProperty($handler, 'filename');
        $this->assertEquals('/var/logs/mautic_test.php', $filename);
        $maxFiles = $this->getProperty($handler, 'maxFiles');
        $this->assertEquals(7, $maxFiles);
    }

    public function testPropertiesAreSetFromCoreParametersHelperWhenDebugModeDisabled(): void
    {
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                function ($key) {
                    switch ($key) {
                        case 'log_path':
                            return '/var/logs';
                        case 'log_file_name':
                            return 'mautic_test.php';
                        case 'max_log_files':
                            return 7;
                        case 'debug':
                            return false;
                    }
                }
            );

        $formatterStub = $this->createStub(FormatterInterface::class);

        $handler = new FileLogHandler($this->coreParametersHelper, $formatterStub);
        $this->assertSame(Level::Notice, $handler->getLevel());
        $this->assertNotSame(spl_object_id($formatterStub), spl_object_id($handler->getFormatter()));

        $filename = $this->getProperty($handler, 'filename');
        $this->assertEquals('/var/logs/mautic_test.php', $filename);
        $maxFiles = $this->getProperty($handler, 'maxFiles');
        $this->assertEquals(7, $maxFiles);
    }

    private function getProperty(FileLogHandler $handler, string $property): mixed
    {
        $reflection = new \ReflectionClass($handler);
        $fileName   = $reflection->getProperty($property);

        return $fileName->getValue($handler);
    }
}
