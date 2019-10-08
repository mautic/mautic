<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CsvHelper;

class CsvHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testSanitizeHeaders()
    {
        $headers = [
            'withoutSpaces',
            ' with spaces ',
            ' left space',
            'right space ',
        ];

        $expected = [
            'withoutSpaces',
            'with spaces',
            'left space',
            'right space',
        ];

        $this->assertEquals($expected, CsvHelper::sanitizeHeaders($headers));
    }

    public function testConvertHeadersIntoFields()
    {
        $headers = [
            'České znáčky',
            '',
            'First Name',
        ];

        $expected = [
            'first_name' => 'First Name',
            'esk_znky'   => 'České znáčky',
        ];

        $this->assertEquals($expected, CsvHelper::convertHeadersIntoFields($headers));
    }
}
