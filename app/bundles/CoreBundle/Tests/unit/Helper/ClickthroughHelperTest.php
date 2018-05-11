<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\ClickthroughHelper;

class ClickthroughHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodingCanBeDecoded()
    {
        $array = ['foo' => 'bar'];

        $this->assertEquals($array, ClickthroughHelper::decodeArrayFromUrl(ClickthroughHelper::encodeArrayForUrl($array)));
    }

    public function testOnlyArraysCanBeDecodedToPreventObjectWakeupVulnerability()
    {
        $this->expectException(\InvalidArgumentException::class);

        ClickthroughHelper::decodeArrayFromUrl(urlencode(base64_encode(serialize(new \stdClass()))));
    }
}
