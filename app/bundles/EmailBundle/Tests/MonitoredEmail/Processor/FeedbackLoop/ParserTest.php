<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\FeedbackLoop;

use Mautic\EmailBundle\MonitoredEmail\Exception\FeedbackLoopNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that an email is found inside a feedback report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop\Parser::parse()
     */
    public function testEmailIsFoundInFeedbackLoopEmail()
    {
        $message            = new Message();
        $message->fblReport = <<<'BODY'
Feedback-Type: abuse
User-Agent: SomeGenerator/1.0
Version: 1
Original-Mail-From: <somespammer@example.net>
Original-Rcpt-To: <user@example.com>
Received-Date: Thu, 8 Mar 2005 14:00:00 EDT
Source-IP: 192.0.2.2
Authentication-Results: mail.example.com
               smtp.mail=somespammer@example.com;
               spf=fail
Reported-Domain: example.net
Reported-Uri: http://example.net/earn_money.html
Reported-Uri: mailto:user@example.com
Removal-Recipient: user@example.com
BODY;

        $parser = new Parser($message);

        $email = $parser->parse();
        $this->assertEquals('user@example.com', $email);
    }

    /**
     * @testdox Test that an exception is thrown if no feedback report is found
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop\Parser::parse()
     */
    public function testExceptionIsThrownWithFblNotFound()
    {
        $this->expectException(FeedbackLoopNotFound::class);

        $message = new Message();
        $parser  = new Parser($message);

        $parser->parse();
    }
}
