<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Symfony\Component\Translation\TranslatorInterface;

class DateHelperTest extends \PHPUnit_Framework_TestCase
{
    private $translator;

    /**
     * @var DateHelper
     */
    private $helper;

    /**
     * @var string
     */
    private static $oldTimezone;

    public static function setupBeforeClass()
    {
        self::$oldTimezone = date_default_timezone_get();
    }

    public static function tearDownAfterClass()
    {
        date_default_timezone_set(self::$oldTimezone);
    }

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->helper     = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator
        );
    }

    public function testStringToText()
    {
        date_default_timezone_set('Etc/GMT-4');
        $time = '2016-01-27 14:30:00';
        $this->assertSame('January 27, 2016 6:30 pm', $this->helper->toText($time, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testStringToTextUtc()
    {
        date_default_timezone_set('UTC');
        $time = '2016-01-27 14:30:00';

        $this->assertSame('January 27, 2016 2:30 pm', $this->helper->toText($time, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testDateTimeToText()
    {
        date_default_timezone_set('Etc/GMT-4');
        $dateTime = new \DateTime('2016-01-27 14:30:00', new \DateTimeZone('UTC'));
        $this->assertSame('January 27, 2016 6:30 pm', $this->helper->toText($dateTime, 'UTC', 'Y-m-d H:i:s', true));
    }

    public function testDateTimeToTextUtc()
    {
        date_default_timezone_set('UTC');
        $dateTime = new \DateTime('2016-01-27 14:30:00', new \DateTimeZone('UTC'));

        $this->assertSame('January 27, 2016 2:30 pm', $this->helper->toText($dateTime, 'UTC', 'Y-m-d H:i:s', true));
    }
}
