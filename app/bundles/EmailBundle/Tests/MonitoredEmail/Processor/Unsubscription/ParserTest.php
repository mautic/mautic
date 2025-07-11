<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Unsubscription;

use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\Parser;

#[\PHPUnit\Framework\Attributes\CoversClass(Parser::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail::class)]
class ParserTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that an email is found inside a feedback report')]
    public function testThatReplyIsDetectedThroughTrackingPixel(): void
    {
        $message              = new Message();
        $message->fromAddress = 'hello@hello.com';
        $message->to          = [
            'test+unsubscribe@test.com' => 'Test Test',
        ];

        $parser = new Parser($message);

        $unsubscribedEmail = $parser->parse();

        $this->assertEquals('hello@hello.com', $unsubscribedEmail->getContactEmail());
        $this->assertEquals('test+unsubscribe@test.com', $unsubscribedEmail->getUnsubscriptionAddress());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that an exeption is thrown if a unsubscription email is not found')]
    public function testExceptionIsThrownWithUnsubscribeNotFound(): void
    {
        $this->expectException(UnsubscriptionNotFound::class);

        $message = new Message();
        $parser  = new Parser($message);

        $parser->parse();
    }
}
