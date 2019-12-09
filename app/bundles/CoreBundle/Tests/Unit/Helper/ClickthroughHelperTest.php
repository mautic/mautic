<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Tests\Unit\Helper\TestResources\WakeupCall;

class ClickthroughHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testEncodingCanBeDecoded()
    {
        $array = ['foo' => 'bar'];

        $this->assertEquals($array, ClickthroughHelper::decodeArrayFromUrl(ClickthroughHelper::encodeArrayForUrl($array)));
    }

    /**
     * @covers \Mautic\CoreBundle\Helper\Serializer::decode
     */
    public function testObjectInArrayIsDetectedOrIgnored()
    {
        $this->expectException(\InvalidArgumentException::class);

        $array = ['foo' => new WakeupCall()];

        ClickthroughHelper::decodeArrayFromUrl(ClickthroughHelper::encodeArrayForUrl($array));
    }

    public function testOnlyArraysCanBeDecodedToPreventObjectWakeupVulnerability()
    {
        $this->expectException(\InvalidArgumentException::class);

        ClickthroughHelper::decodeArrayFromUrl(urlencode(base64_encode(serialize(new \stdClass()))));
    }

    public function testEmptyStringDoesNotThrowException()
    {
        $array = [];

        $this->assertEquals($array, ClickthroughHelper::decodeArrayFromUrl(''));
    }
}
