<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\Serializer;
use PHPUnit\Framework\TestCase;

final class SerializerTest extends TestCase
{
    public function testDecodeUnserializesArray(): void
    {
        $data = ['addToLists' => [1, 2], 'removeFromLists' => [3]];

        $this->assertSame($data, Serializer::decode(serialize($data)));
    }

    public function testDecodeUnserializesScalars(): void
    {
        $this->assertSame('foo', Serializer::decode(serialize('foo')));
        $this->assertSame(42, Serializer::decode(serialize(42)));
        $this->assertTrue(Serializer::decode(serialize(true)));
    }

    public function testDecodeReturnsFalseForSerializedFalse(): void
    {
        $this->assertFalse(Serializer::decode('b:0;'));
    }

    public function testDecodeRejectsSerializedObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Serializer::decode('O:8:"stdClass":1:{s:3:"cmd";s:2:"id";}');
    }

    public function testDecodeRejectsSerializedObjectNestedInArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Serializer::decode(serialize(['evil' => new \stdClass()]));
    }

    public function testDecodeThrowsOnInvalidString(): void
    {
        $this->expectException(\ErrorException::class);

        Serializer::decode('not-serialized');
    }
}
