<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Templating\Helper;

use Mautic\CoreBundle\Templating\Helper\ContentHelper;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testAssetContext()
    {
        $dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $delegationMock = $this->getMockBuilder(DelegatingEngine::class)
             ->disableOriginalConstructor()
             ->getMock();

        $contentHelper = new ContentHelper($dispatcherMock, $delegationMock);
        $sample        = '<h1>Hello World</h1>

        <script>
            console.log("do not mind me");
        </script>';

        $expected = '<h1>Hello World</h1>

        [script]
            console.log("do not mind me");
        [/script]';

        $result = $contentHelper->showScriptTags($sample);

        $this->assertEquals($expected, $result);
    }
}
