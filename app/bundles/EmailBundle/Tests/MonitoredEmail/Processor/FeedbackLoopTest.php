<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\MonitoredEmail\Search\Result;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Monolog\Logger;

#[\PHPUnit\Framework\Attributes\CoversClass(FeedbackLoop::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(Result::class)]
class FeedbackLoopTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that the message is processed appropriately')]
    public function testContactIsFoundFromMessage(): void
    {
        $contactFinder = $this->createMock(ContactFinder::class);
        $contactFinder->method('find')
            ->willReturnCallback(
                function ($email) {
                    $stat = new Stat();

                    $lead = new Lead();
                    $lead->setEmail($email);
                    $stat->setLead($lead);

                    $email = new Email();
                    $stat->setEmail($email);

                    $result = new Result();
                    $result->setStat($stat);
                    $result->setContacts(
                        [
                            $lead,
                        ]
                    );

                    return $result;
                }
            );

        $translator = $this->createMock(Translator::class);

        $logger = $this->createMock(Logger::class);

        $doNotContact = $this->createMock(DoNotContact::class);

        $processor = new FeedbackLoop($contactFinder, $translator, $logger, $doNotContact);

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

        $this->assertTrue($processor->process($message));
    }
}
