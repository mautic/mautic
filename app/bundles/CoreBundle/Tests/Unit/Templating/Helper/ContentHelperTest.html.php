<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Templating\Helper;

use Mautic\CoreBundle\Templating\Helper\ContentHelper;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentHelper
     */
    private $contentHelper;

    protected function setUp()
    {
        $dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $delegationMock = $this->getMockBuilder(DelegatingEngine::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentHelper = new contenthelper($delegationMock, $dispatcherMock);
    }

    public function testShowScriptTagsContext()
    {
        $this->doShowTagsContext('script');
    }

    public function testShowStyleTagsContext()
    {
        $this->doShowTagsContext('style');
    }

    public function testShowScriptTagsInlineContext()
    {
        $sample   = 'Hi <script>console.log("do not mind me");</script> <script type="text/javascript">console.log("do not mind me");</script>';
        $expected = 'Hi [script]console.log("do not mind me");[/script] [script type="text/javascript"]console.log("do not mind me");[/script]';

        $result = $this->contentHelper->showScriptTags($sample);

        $this->assertEquals($expected, $result);
    }

    private function doShowTagsContext($tag)
    {
        $sample        = '<h1>Hello World</h1>

        <'.$tag.'>
            console.log("do not mind me");
        </'.$tag.'>
        
        <'.$tag.' type="text/javascript">
            console.log("do not mind me");
        </'.$tag.'>';

        $expected = '<h1>Hello World</h1>

        ['.$tag.']
            console.log("do not mind me");
        [/'.$tag.']
        
        ['.$tag.' type="text/javascript"]
            console.log("do not mind me");
        [/'.$tag.']';

        $result = $this->contentHelper->showScriptTags($sample);

        $this->assertEquals($expected, $result);
    }
}
