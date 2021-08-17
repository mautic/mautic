<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Symfony\Component\Translation\TranslatorInterface;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var DateHelper
     */
    private $helper;

    /**
     * @var string
     */
    private static $oldTimezone;

    /**
     * @var CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $coreParametersHelper;

    public static function setUpBeforeClass(): void
    {
        self::$oldTimezone = date_default_timezone_get();
    }

    public static function tearDownAfterClass(): void
    {
        date_default_timezone_set(self::$oldTimezone);
    }

    protected function setUp(): void
    {
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->helper               = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator,
            $this->coreParametersHelper
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

    public function testToTextWithConfigurationToTime()
    {
        $this->coreParametersHelper->method('get')
            ->with('date_format_timeonly')
            ->willReturn('00:00:00');

        $this->translator->method('trans')
            ->willReturnCallback(
                function (string $key, array $parameters = []) {
                    if (isset($parameters['%time%'])) {
                        return $parameters['%time%'];
                    }
                }
            );

        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->assertSame('00:00:00', $this->helper->toText($dateTime));
    }

    public function testFullConcat()
    {
        date_default_timezone_set('Europe/Paris');
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2021-02-21 18:00:00', new \DateTimeZone('UTC'));
        $result   = $this->helper->toFullConcat($dateTime, 'UTC');
        $this->assertEquals($result, 'February 21, 2021 7:00 pm');
    }
}
