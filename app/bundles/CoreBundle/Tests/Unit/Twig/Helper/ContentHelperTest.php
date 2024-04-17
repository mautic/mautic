<?php

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Twig\Helper\ContentHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class ContentHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentHelper
     */
    private $contentHelper;

    protected function setUp(): void
    {
        $dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $delegationMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentHelper = new ContentHelper($delegationMock, $dispatcherMock);
    }

    public function testShowScriptTagsContext(): void
    {
        $this->doShowTagsContext('script');
    }

    public function testShowStyleTagsContext(): void
    {
        $this->doShowTagsContext('style');
    }

    public function testShowScriptTagsInlineContext(): void
    {
        $sample   = 'Hi <script>console.log("do not mind me");</script> <script type="text/javascript">console.log("do not mind me");</script>';
        $expected = 'Hi [script]console.log("do not mind me");[/script] [script type="text/javascript"]console.log("do not mind me");[/script]';

        $result = $this->contentHelper->showScriptTags($sample);

        $this->assertEquals($expected, $result);
    }

    private function doShowTagsContext(string $tag): void
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
