<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\UrlMatcher;

class UrlMatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testUrlIsFound()
    {
        $urls = [
            'google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'google.com'));
    }

    public function testUrlWithSlashIsMatched()
    {
        $urls = [
            'https://google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com'));
    }

    public function testUrlWithEscapedSlashesIsMatched()
    {
        $urls = [
            'https:\/\/google.com\/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
    }
}
