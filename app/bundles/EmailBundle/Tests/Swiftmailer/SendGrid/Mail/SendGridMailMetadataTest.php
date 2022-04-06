<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use SendGrid\BccSettings;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\ReplyTo;

class SendGridMailMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testBaseMessage()
    {
        $sendGridMailMetadata = new SendGridMailMetadata();

        $message = $this->getMockBuilder(MauticMessage::class)
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

        $message->expects($this->exactly(1))
            ->method('getTo')
            ->with()
            ->willReturn(['to@example.com' => 'to@example.com']);

        $message->expects($this->exactly(1))
            ->method('getMetadata')
            ->with()
            ->willReturn(['to@example.com' => ['emailId' => 123]]);

        $mail = new Mail('from', 'subject', 'to', 'content');

        $sendGridMailMetadata->addMetadataToMail($mail, $message);

        $replyTo = new ReplyTo('email@example.com');
        $this->assertEquals($replyTo, $mail->getReplyTo());

        $customArgs = ['mautic_metadata' => serialize(['email@example.com' => ['emailId' => 123]])];
        $this->assertEquals($customArgs, $mail->getCustomArgs());

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
