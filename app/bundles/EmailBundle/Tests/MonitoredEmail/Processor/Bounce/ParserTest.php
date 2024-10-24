<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Test that a bounce is found through DsnReport
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser::parse
     */
    public function testBouncedEmailIsReturnedFromParsedDsnReport(): void
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
        $bounce = $parser->parse();

        $this->assertSame('sdfgsdfg@seznan.cz', $bounce->getContactEmail());
    }

    /**
     * @testdox Test that a bounce is found through body
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Parser::parse
     */
    public function testBouncedEmailIsReturnedFromParsedBody(): void
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
        $bounce = $parser->parse();

        $this->assertSame('recipient@example.net', $bounce->getContactEmail());
    }
}
