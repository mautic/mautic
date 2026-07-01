<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor;
use PHPUnit\Framework\TestCase;

final class IntNullableProcessorTest extends TestCase
{
    public function testNullReturnedIfNullValue(): void
    {
        $getEnv = fn (string $name): null => null;

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertNull($value);
    }

    public function testIntReturnedIfNotNull(): void
    {
        $getEnv = fn (string $name): string => '0';

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(0, $value);
    }

    public function testIntReturnedIfEmptyString(): void
    {
        $getEnv = fn (string $name): string => '';

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(0, $value);
    }

    public function testIntReturnedIfInt(): void
    {
        $getEnv = fn (string $name): int => 12;

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(12, $value);
    }
}
