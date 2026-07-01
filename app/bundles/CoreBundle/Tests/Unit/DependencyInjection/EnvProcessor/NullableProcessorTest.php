<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor;
use PHPUnit\Framework\TestCase;

final class NullableProcessorTest extends TestCase
{
    public function testNullReturnedIfEmptyString(): void
    {
        $getEnv = fn (string $name): string => '';

        $processor = new NullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertNull($value);
    }

    public function testValueReturnedIfNotEmptyString(): void
    {
        $getEnv = fn (string $name): string => 'foobar';

        $processor = new NullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame('foobar', $value);
    }
}
