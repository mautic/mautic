<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Monolog;

use Mautic\CoreBundle\Monolog\LogProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class LogProcessorTest extends TestCase
{
    public function testLogProcessor(): void
    {
        $logProcessor = new LogProcessor();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'mautic',
            level: Level::Debug,
            message: 'This is debug message',
            context: [],
            extra: ['existing' => 'value']
        );

        $processed = $logProcessor($record);

        $this->assertSame('value', $processed->extra['existing']);
        $this->assertArrayHasKey('hostname', $processed->extra);
        $this->assertArrayHasKey('pid', $processed->extra);
        $this->assertIsString($processed->extra['hostname']);
        $this->assertIsInt($processed->extra['pid']);
    }
}
