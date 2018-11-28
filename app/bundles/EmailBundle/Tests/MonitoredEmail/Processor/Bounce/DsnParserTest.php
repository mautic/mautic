<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser;

class DsnParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that a BouncedEmail is returned from a dsn report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::getBounce()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::parse()
     */
    public function testBouncedEmailIsReturnedFromParsedDsnReport()
    {
        $message            = new Message();
        $message->dsnReport = <<<'DSN'
Original-Recipient: sdfgsdfg@seznan.cz
Final-Recipient: rfc822;sdfgsdfg@seznan.cz
Action: failed
Status: 5.4.4
Diagnostic-Code: DNS; Host not found
DSN;
        $parser = new DsnParser();
        $bounce = $parser->getBounce($message);

        $this->assertInstanceOf(BouncedEmail::class, $bounce);
        $this->assertEquals('sdfgsdfg@seznan.cz', $bounce->getContactEmail());
        $this->assertEquals(Category::DNS_UNKNOWN, $bounce->getRuleCategory());
        $this->assertEquals(Type::HARD, $bounce->getType());
        $this->assertTrue($bounce->isFinal());
    }

    /**
     * @testdox Test that an exception is thrown if a bounce cannot be found in a dsn report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::getBounce()
     */
    public function testBounceNotFoundFromBadDsnReport()
    {
        $this->expectException(BounceNotFound::class);

        $message            = new Message();
        $message->dsnReport = 'BAD';
        $parser             = new DsnParser();
        $parser->getBounce($message);
    }
}
