<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor;
use PHPUnit\Framework\TestCase;

class NullableProcessorTest extends TestCase
{
    public function testNullReturnedIfEmptyString(): void
    {
        $getEnv = fn (string $name) => '';

        $processor = new NullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertNull($value);
    }

    public function testValueReturnedIfNotEmptyString(): void
    {
        $getEnv = fn (string $name) => 'foobar';

        $processor = new NullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertEquals('foobar', $value);
    }
}
