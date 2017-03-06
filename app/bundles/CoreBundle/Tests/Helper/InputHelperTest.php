<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\InputHelper;

/**
 * Class InputHelperTest test.
 */
class InputHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox The guessTimezoneFromOffset returns correct values
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelperTest::guessTimezoneFromOffset
     */
    public function testHtmlFilter()
    {
        $outlookXML = '<!--[if gte mso 9]><xml>
 <o:OfficeDocumentSettings>
  <o:AllowPNG/>
  <o:PixelsPerInch>96</o:PixelsPerInch>
 </o:OfficeDocumentSettings>
</xml><![endif]-->';
        $html5Doctype            = '<!DOCTYPE html>';
        $html5DoctypeWithContent = '<!DOCTYPE html>
        <html>
        </html>';
        $xhtml1Doctype = '<!DOCTYPE html PUBLIC
  "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        $cdata = '<![CDATA[content]]>';

        $samples = [
            $outlookXML                => $outlookXML,
            $html5Doctype              => $html5Doctype,
            $html5DoctypeWithContent   => $html5DoctypeWithContent,
            $xhtml1Doctype             => $xhtml1Doctype,
            $cdata                     => $cdata,
            '<applet>content</applet>' => 'content',
        ];

        foreach ($samples as $sample => $expected) {
            $actual = InputHelper::html($sample);
            $this->assertEquals($expected, $actual);
        }
    }
}
