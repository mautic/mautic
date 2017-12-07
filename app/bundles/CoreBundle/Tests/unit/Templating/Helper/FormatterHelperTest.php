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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;

class FormatterHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testStrictHtmlFormatIsRemovingScriptTags()
    {
        $factoryMock = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper = new FormatterHelper($factoryMock);

        $sample = '<a href="/index_dev.php/s/webhooks/view/31" data-toggle="ajax">test</a> has been stopped because the response HTTP code was 410, which means the reciever doesn\'t want us to send more requests.<script>console.log(\'script is running\');</script><SCRIPT>console.log(\'CAPITAL script is running\');</SCRIPT>';

        $expected = '<a href="/index_dev.php/s/webhooks/view/31" data-toggle="ajax">test</a> has been stopped because the response HTTP code was 410, which means the reciever doesn\'t want us to send more requests.console.log(\'script is running\');console.log(\'CAPITAL script is running\');';

        $result = $helper->_($sample, 'html');

        $this->assertEquals($expected, $result);
    }
}
