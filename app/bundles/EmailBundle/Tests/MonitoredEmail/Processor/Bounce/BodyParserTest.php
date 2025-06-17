<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BodyParser;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;

#[\PHPUnit\Framework\Attributes\CoversClass(BodyParser::class)]
class BodyParserTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that a BouncedEmail is returned from a bounce detected in the body')]
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
        $parser = new BodyParser();
        $bounce = $parser->getBounce($message);

        $this->assertEquals('recipient@example.net', $bounce->getContactEmail());
        $this->assertEquals(Category::UNKNOWN, $bounce->getRuleCategory());
        $this->assertEquals(Type::HARD, $bounce->getType());
        $this->assertTrue($bounce->isFinal());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that an exception is thrown if a bounce cannot be found in the body')]
    public function testBounceNotFoundFromBadDsnReport(): void
    {
        $this->expectException(BounceNotFound::class);

        $message            = new Message();
        $message->textPlain = 'BAD';
        $parser             = new BodyParser();
        $parser->getBounce($message);
    }
}
