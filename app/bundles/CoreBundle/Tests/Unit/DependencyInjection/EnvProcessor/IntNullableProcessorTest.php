<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor;
use PHPUnit\Framework\TestCase;

class IntNullableProcessorTest extends TestCase
{
    public function testNullReturnedIfNullValue(): void
    {
        $getEnv = fn (string $name) => null;

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertNull($value);
    }

    public function testIntReturnedIfNotNull(): void
    {
        $getEnv = fn (string $name) => '0';

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(0, $value);
    }

    public function testIntReturnedIfEmptyString(): void
    {
        $getEnv = fn (string $name) => '';

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(0, $value);
    }

    public function testIntReturnedIfInt(): void
    {
        $getEnv = fn (string $name) => 12;

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(12, $value);
    }
}
