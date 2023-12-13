<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CsvHelper;

class CsvHelperTest extends \PHPUnit\Framework\TestCase
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
        ];

        $expected = [
            'first_name' => 'First Name',
            'esk_znky'   => 'České znáčky',
        ];

        $this->assertEquals($expected, CsvHelper::convertHeadersIntoFields($headers));
    }
}
