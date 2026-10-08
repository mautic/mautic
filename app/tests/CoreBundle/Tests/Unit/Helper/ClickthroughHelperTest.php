<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\CoreBundle\Tests\Unit\Helper\TestResources\WakeupCall;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Serializer::class)]
final class ClickthroughHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testEncodingCanBeDecoded(): void
    {
        $array = ['foo' => 'bar'];

        $this->assertSame($array, ClickthroughHelper::decodeArrayFromUrl(ClickthroughHelper::encodeArrayForUrl($array)));
    }

    public function testObjectInArrayIsDetectedOrIgnored(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $array = ['foo' => new WakeupCall()];

        ClickthroughHelper::decodeArrayFromUrl(ClickthroughHelper::encodeArrayForUrl($array));
    }

    public function testOnlyArraysCanBeDecodedToPreventObjectWakeupVulnerability(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ClickthroughHelper::decodeArrayFromUrl(urlencode(base64_encode(serialize(new \stdClass()))));
    }

    public function testEmptyStringDoesNotThrowException(): void
    {
        $array = [];

        $this->assertSame($array, ClickthroughHelper::decodeArrayFromUrl(''));
    }
}
