<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Unsubscribe;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\Parser;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that an email is found inside a feedback report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\Parser::parse()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail::getContactEmail()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail::getUnsubscriptionAddress()
     */
    public function testThatReplyIsDetectedThroughTrackingPixel()
    {
        $message              = new Message();
        $message->fromAddress = 'hello@hello.com';
        $message->to          = [
            'test+unsubscribe@test.com' => 'Test Test',
        ];

        $parser = new Parser($message);

        $unsubscribedEmail = $parser->parse();
        $this->assertInstanceOf(UnsubscribedEmail::class, $unsubscribedEmail);

        $this->assertEquals('hello@hello.com', $unsubscribedEmail->getContactEmail());
        $this->assertEquals('test+unsubscribe@test.com', $unsubscribedEmail->getUnsubscriptionAddress());
    }

    /**
     * @testdox Test that an exeption is thrown if a unsubscription email is not found
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\Parser::parse()
     */
    public function testExceptionIsThrownWithUnsubscribeNotFound()
    {
        $this->expectException(UnsubscriptionNotFound::class);

        $message = new Message();
        $parser  = new Parser($message);

        $parser->parse();
    }
}
