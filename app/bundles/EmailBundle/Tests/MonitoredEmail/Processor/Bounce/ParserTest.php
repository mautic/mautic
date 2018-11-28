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

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that a bounce is found through DsnReport
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser::parse()
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

        $parser = new Parser($message);
        $bounce = $parser->parse($message);

        $this->assertInstanceOf(BouncedEmail::class, $bounce);
    }

    /**
     * @testdox Test that a bounce is found through body
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser::parse()
     */
    public function testBouncedEmailIsReturnedFromParsedBody()
    {
        $message            = new Message();
        $message->textPlain = <<<'BODY'
Please direct further questions regarding this message to your e-mail
administrator.

--AOL Postmaster



   ----- The following addresses had permanent fatal errors -----
<recipient@example.net>

   ----- Transcript of session follows -----
... while talking to air-yi01.mail.aol.com.:
>>> RCPT To:<recipient@example.net>
<<< 550 MAILBOX NOT FOUND
550 <recipient@example.net>... User unknown
BODY;

        $parser = new Parser($message);
        $bounce = $parser->parse($message);

        $this->assertInstanceOf(BouncedEmail::class, $bounce);
    }
}
