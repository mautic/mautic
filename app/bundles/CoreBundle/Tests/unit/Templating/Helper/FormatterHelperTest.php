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

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Symfony\Component\Translation\TranslatorInterface;

class FormatterHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testStrictHtmlFormatIsRemovingScriptTags()
    {
        $appVersion = $this->getMockBuilder(AppVersion::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dateHelper = $this->getMockBuilder(DateHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper = new FormatterHelper($appVersion, $dateHelper, $translator);

        $sample = '<a href="/index_dev.php/s/webhooks/view/31" data-toggle="ajax">test</a> has been stopped because the response HTTP code was 410, which means the reciever doesn\'t want us to send more requests.<script>console.log(\'script is running\');</script><SCRIPT>console.log(\'CAPITAL script is running\');</SCRIPT>';

        $expected = '<a href="/index_dev.php/s/webhooks/view/31" data-toggle="ajax">test</a> has been stopped because the response HTTP code was 410, which means the reciever doesn\'t want us to send more requests.console.log(\'script is running\');console.log(\'CAPITAL script is running\');';

        $result = $helper->_($sample, 'html');

        $this->assertEquals($expected, $result);
    }

    public function testBooleanFormat()
    {
        $appVersion = $this->getMockBuilder(AppVersion::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dateHelper = $this->getMockBuilder(DateHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator->expects($this->at(0))
            ->method('trans')
            ->with('mautic.core.yes')
            ->willReturn('yes');
        $translator->expects($this->at(1))
            ->method('trans')
            ->with('mautic.core.no')
            ->willReturn('no');

        $helper = new FormatterHelper($appVersion, $dateHelper, $translator);

        $result = $helper->_(1, 'bool');
        $this->assertEquals('yes', $result);

        $result = $helper->_(0, 'bool');
        $this->assertEquals('no', $result);
    }
}
