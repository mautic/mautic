<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser;

class DsnParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Test that a BouncedEmail is returned from a dsn report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::getBounce
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::parse
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
        $parser = new DsnParser();
        $bounce = $parser->getBounce($message);

        $this->assertEquals('sdfgsdfg@seznan.cz', $bounce->getContactEmail());
        $this->assertEquals(Category::DNS_UNKNOWN, $bounce->getRuleCategory());
        $this->assertEquals(Type::HARD, $bounce->getType());
        $this->assertTrue($bounce->isFinal());
    }

    /**
     * @testdox Test a Postfix BouncedEmail is returned from a dsn report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::getBounce
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::parse
     */
    public function testPostfixBouncedEmailIsReturnedFromParsedDsnReport(): void
    {
        $message            = new Message();
        $message->dsnReport = <<<'DSN'
Final-Recipient: rfc822; aaaaaaaaaaaaa@yoursite.com
Original-Recipient: rfc822;aaaaaaaaaaaaa@yoursite.com
Action: failed
Status: 5.1.1
Remote-MTA: dns; mail-server.yoursite.com
Diagnostic-Code: smtp; 550 5.1.1 <aaaaaaaaaaaaa@yoursite.com> User doesn't
    exist: aaaaaaaaaaaaa@yoursite.com
DSN;

        $parser = new DsnParser();
        $bounce = $parser->getBounce($message);

        $this->assertEquals('aaaaaaaaaaaaa@yoursite.com', $bounce->getContactEmail());
        $this->assertEquals(Category::UNKNOWN, $bounce->getRuleCategory());
        $this->assertEquals(Type::HARD, $bounce->getType());
        $this->assertTrue($bounce->isFinal());
    }

    /**
     * @testdox Test that an exception is thrown if a bounce cannot be found in a dsn report
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser::getBounce
     */
    public function testBounceNotFoundFromBadDsnReport(): void
    {
        $this->expectException(BounceNotFound::class);

        $message            = new Message();
        $message->dsnReport = 'BAD';
        $parser             = new DsnParser();
        $parser->getBounce($message);
    }
}
