<?php

namespace Mautic\CoreBundle\Tests\Unit\Monolog\Handler;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Monolog\Handler\FileLogHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileLogHandlerTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var FormatterInterface|MockObject
     */
    private $formatter;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->formatter            = $this->createMock(FormatterInterface::class);
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

        $handler = new FileLogHandler($this->coreParametersHelper, $this->formatter);
        $this->assertEquals(Logger::DEBUG, $handler->getLevel());
        $this->assertEquals(spl_object_id($this->formatter), spl_object_id($handler->getFormatter()));

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

        $handler = new FileLogHandler($this->coreParametersHelper, $this->formatter);
        $this->assertEquals(Logger::NOTICE, $handler->getLevel());
        $this->assertNotEquals(spl_object_id($this->formatter), spl_object_id($handler->getFormatter()));

        $filename = $this->getProperty($handler, 'filename');
        $this->assertEquals('/var/logs/mautic_test.php', $filename);
        $maxFiles = $this->getProperty($handler, 'maxFiles');
        $this->assertEquals(7, $maxFiles);
    }

    private function getProperty(FileLogHandler $handler, string $property)
    {
        $reflection = new \ReflectionClass($handler);
        $fileName   = $reflection->getProperty($property);
        $fileName->setAccessible(true);

        return $fileName->getValue($handler);
    }
}
