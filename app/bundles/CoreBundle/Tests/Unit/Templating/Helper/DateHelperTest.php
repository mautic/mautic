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

use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Symfony\Component\Translation\TranslatorInterface;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    private $translator;

    /**
     * @var DateHelper
     */
    private $helper;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->helper     = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator
        );
    }

    public function testToTextWithPragueTimezone()
    {
        $dateTime    = new \DateTime('2016-01-27 13:30:00', new \DateTimeZone('UTC'));
        $regexForDst = '/^January 27, 2016 [1,2]:30 pm$/';

        $this->assertRegExp($regexForDst, $this->helper->toText($dateTime, 'Europe/Prague', 'Y-m-d H:i:s', true));
    }

    public function testToTextWithUtcTimezone()
    {
        $dateTime = new \DateTime('2017-11-20 15:45:00', new \DateTimeZone('UTC'));

        $this->assertSame('November 20, 2017 3:45 pm', $this->helper->toText($dateTime, 'UTC', 'Y-m-d H:i:s', true));
    }
}
