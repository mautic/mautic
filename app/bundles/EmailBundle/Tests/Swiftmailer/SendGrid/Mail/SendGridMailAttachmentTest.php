<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use SendGrid\Mail;

class SendGridMailAttachmentTest extends \PHPUnit\Framework\TestCase
{
    public function testNotMauticMessageWithAttachment(): void
    {
        $sendGridMailAttachment = new SendGridMailAttachment();

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->exactly(2))->method('getChildren')->will($this->onConsecutiveCalls([
            new \Swift_Attachment('This is the plain text attachment.', 'hello.txt', 'text/plain'),
        ], [
            new \Swift_Attachment('This is the plain text attachment.', 'hello.txt', 'text/plain'),
        ]));

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail->expects($this->once())
            ->method('addAttachment');

        $sendGridMailAttachment->addAttachmentsToMail($mail, $message);
    }

    public function testNotMauticMessageWithoutAttachment(): void
    {
        $sendGridMailAttachment = new SendGridMailAttachment();

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail->expects($this->never())
            ->method('addAttachment');

        $sendGridMailAttachment->addAttachmentsToMail($mail, $message);
    }

    public function testNoAttachment()
    {
        $sendGridMailAttachment = new SendGridMailAttachment();

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('getAttachments')
            ->willReturn([]);

        $mail->expects($this->never())
            ->method('addAttachment');

        $sendGridMailAttachment->addAttachmentsToMail($mail, $message);
    }
}
