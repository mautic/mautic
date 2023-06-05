<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use SendGrid\BccSettings;
use SendGrid\Mail\Content;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\MailSettings;
use SendGrid\Mail\ReplyTo;
use SendGrid\Mail\Subject;
use SendGrid\Mail\To;

class SendGridMailMetadataTest extends \PHPUnit\Framework\TestCase
{
    private function make_header(string $key, string $value): \Swift_Mime_Header
    {
        $header = new \Swift_Mime_Headers_OpenDKIMHeader($key);
        $header->setValue($value);

        return $header;
    }

    public function testBaseMessage()
    {
        $sendGridMailMetadata = new SendGridMailMetadata();

        $randomvalue = rand(-100, 100).'';
        $headers     = $this->createMock(\Swift_Mime_SimpleHeaderSet::class);

        $headers->expects($this->once())
            ->method('getAll')
            ->willReturn([
                $this->make_header('X-FOO', 'Bar'),
                $this->make_header('X-rand', $randomvalue),
                $this->make_header('to', 'nobody@email.com'),
            ]);

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->exactly(2))
            ->method('getReplyTo')
            ->with()
            ->willReturn(['email@example.com' => 'email@example.com']);

        $message->expects($this->exactly(2))
            ->method('getBcc')
            ->with()
            ->willReturn(['bcc@example.com' => 'bcc@example.com']);

        $message->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $mail = new Mail(
            new From('email@example.com'),
            new To('nobody@email.com'),
            new Subject('subject'),
            new Content('content')
        );

        $sendGridMailMetadata->addMetadataToMail($mail, $message);

        $replyTo = new ReplyTo('email@example.com');
        $this->assertEquals($replyTo, $mail->getReplyTo());

        // Header "to" should be ignored
        $this->assertEquals([
            'X-FOO'  => 'Bar',
            'X-rand' => $randomvalue,
        ], $mail->getheaders());

        /**
         * @var MailSettings
         * @var BccSettings  $bccSettings
         */
        $mailSettings = $mail->getMailSettings();
        $bccSettings  = $mailSettings->getBccSettings();

        $this->assertSame('bcc@example.com', $bccSettings->getEmail());
        $this->assertTrue($bccSettings->getEnable());
    }
}
