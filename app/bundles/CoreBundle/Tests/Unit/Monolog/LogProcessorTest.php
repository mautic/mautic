<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Monolog;

use Mautic\CoreBundle\Monolog\LogProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class LogProcessorTest extends TestCase
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

        Assert::assertSame('value', $processed->extra['existing']);
        Assert::assertArrayHasKey('hostname', $processed->extra);
        Assert::assertArrayHasKey('pid', $processed->extra);
        Assert::assertIsString($processed->extra['hostname']);
        Assert::assertIsInt($processed->extra['pid']);
    }
}
