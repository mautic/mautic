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

use Mautic\CoreBundle\Helper\DateRelativeParser;

class DateRelativeParsersTest extends \PHPUnit_Framework_TestCase
{
    private $dictionary;

    protected function setUp()
    {
        $this->dictionary = ['anniversary' => 'anniversary'];
    }

    public function testRelativeDate()
    {
        $dateRelativeParser = new DateRelativeParser($this->dictionary, '+1 day');
        $this->assertTrue($dateRelativeParser->hasRelativeDate());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'anniversary +1 day');
        $this->assertTrue($dateRelativeParser->hasRelativeDate());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, '2019-01-01');
        $this->assertFalse($dateRelativeParser->hasRelativeDate());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, '+1 day', ['datetime', 'date']);
        $this->assertTrue($dateRelativeParser->hasRelativeDate());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'datetime +1 day', ['datetime', 'date']);
        $this->assertTrue($dateRelativeParser->hasRelativeDate());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'date +1 day', ['datetime', 'date']);
        $this->assertTrue($dateRelativeParser->hasRelativeDate());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'date +1 day', ['datetime', 'date']);
        $this->assertEquals('+1 day', $dateRelativeParser->getTimeframePart());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'date +1 day', ['datetime', 'date']);
        $this->assertEquals('date', $dateRelativeParser->getPrefix());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'date anniversary +1 day', ['datetime', 'date']);
        $this->assertEquals('+1 day', $dateRelativeParser->getTimeframePart());

        $dateRelativeParser = new DateRelativeParser($this->dictionary, 'date anniversary +1 day', ['datetime', 'date']);
        $this->assertEquals('date', $dateRelativeParser->getPrefix());
        $this->assertEquals(date('Y').'-07-07', $dateRelativeParser->getAnniversaryDate('2018-07-07'));
    }
}
