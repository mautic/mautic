<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\MonitoredEmail\Search\Result;
use Mautic\EmailBundle\Tests\MonitoredEmail\Transport\TestTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Monolog\Logger;
use Symfony\Component\Mailer\Transport\NullTransport;

final class BounceTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that the transport interface processes the message appropriately')]
    public function testProcessorInterfaceProcessesMessage(): void
    {
        $transport     = new TestTransport();
        $contactFinder = $this->createMock(ContactFinder::class);
        $contactFinder->method('find')
            ->willReturnCallback(
                function ($email, $bounceAddress): Result {
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

        $emailStatModel = $this->createMock(EmailStatModel::class);
        $emailStatModel->expects($this->once())
            ->method('saveEntity');

        $leadModel = $this->createStub(LeadModel::class);

        $translator = $this->createStub(Translator::class);

        $logger = $this->createStub(Logger::class);

        $doNotContact = $this->createStub(DoNotContact::class);

        $bouncer = new Bounce($transport, $contactFinder, $emailStatModel, $leadModel, $translator, $logger, $doNotContact);

        $message = new Message();
        $this->assertTrue($bouncer->process($message));
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that the message is processed appropriately')]
    public function testContactIsFoundFromMessageAndDncRecordAdded(): void
    {
        $transport     = new NullTransport();
        $contactFinder = $this->createMock(ContactFinder::class);
        $contactFinder->method('find')
            ->willReturnCallback(
                function ($email, $bounceAddress): Result {
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

        $emailStatModel = $this->createMock(EmailStatModel::class);
        $emailStatModel->expects($this->once())
            ->method('saveEntity');

        $leadModel = $this->createStub(LeadModel::class);

        $translator = $this->createStub(Translator::class);

        $logger = $this->createStub(Logger::class);

        $doNotContact = $this->createStub(DoNotContact::class);

        $bouncer = new Bounce($transport, $contactFinder, $emailStatModel, $leadModel, $translator, $logger, $doNotContact);

        $message            = new Message();
        $message->to        = ['contact+bounce_123abc@test.com' => null];
        $message->dsnReport = <<<'DSN'
Original-Recipient: sdfgsdfg@seznan.cz
Final-Recipient: rfc822;sdfgsdfg@seznan.cz
Action: failed
Status: 5.4.4
Diagnostic-Code: DNS; Host not found
DSN;

        $this->assertTrue($bouncer->process($message));
    }
}
