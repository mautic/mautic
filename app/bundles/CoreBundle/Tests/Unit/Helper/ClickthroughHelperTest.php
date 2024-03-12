<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\Clickthrough\ClickthroughKeyConverter;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Tests\Unit\Helper\TestResources\WakeupCall;

class ClickthroughHelperTest extends \PHPUnit\Framework\TestCase
{
    private ClickthroughHelper $clickthroughHelper;

    protected function setUp(): void
    {
        $shortKeyConverter        = new ClickthroughKeyConverter();
        $this->clickthroughHelper = new ClickthroughHelper($shortKeyConverter);
    }

    public function testEncodingCanBeDecoded(): void
    {
        $array = ['foo' => 'bar'];

        $this->assertEquals($array, $this->clickthroughHelper->decode($this->clickthroughHelper->encode($array)));
    }

    /**
     * @covers \Mautic\CoreBundle\Helper\Serializer::decode
     */
    public function testObjectInArrayIsDetectedOrIgnored(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $array = ['foo' => new WakeupCall()];

        $this->clickthroughHelper->decode(ClickthroughHelper::encodeArrayForUrl($array));
    }

    public function testOnlyArraysCanBeDecodedToPreventObjectWakeupVulnerability(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->clickthroughHelper->decode(urlencode(base64_encode(serialize(new \stdClass()))));
    }

    public function testEmptyStringDoesNotThrowException(): void
    {
        $array = [];

        $this->assertEquals($array, $this->clickthroughHelper->decode(''));
    }

    public function testDecodeWithInvalidString(): void
    {
        $invalidString = 'invalidString';

        $this->expectException(InvalidDecodedStringException::class);

        $this->clickthroughHelper->decode($invalidString);
    }
}
