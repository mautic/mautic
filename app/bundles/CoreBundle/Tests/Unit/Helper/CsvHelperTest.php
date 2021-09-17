<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CsvHelper;
use PHPUnit\Framework\TestCase;

class CsvHelperTest extends TestCase
{
    public function testSanitizeHeaders(): void
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

    public function testConvertHeadersIntoFields(): void
    {
        $headers = [
            'České znáčky',
            '',
            'First Name',
            'File',
        ];

        $expected = [
            'h_first_name' => 'First Name',
            'h_esk_znky'   => 'České znáčky',
            'h_file'       => 'File',
        ];

        $this->assertEquals($expected, CsvHelper::convertHeadersIntoFields($headers));
    }
}
