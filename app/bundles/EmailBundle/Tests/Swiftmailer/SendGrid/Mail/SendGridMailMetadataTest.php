<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use SendGrid\BccSettings;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\ReplyTo;

class SendGridMailMetadataTest extends \PHPUnit\Framework\TestCase
{
    private function make_header(string $key, string $value): MockObject
    {
        $an_header = $this->getMockBuilder(\Swift_Mime_Header::class)
            ->disableOriginalConstructor()
            ->getMock();

        $an_header->method('getFieldName')
            ->willReturn($key);

        $an_header->method('getFieldBody')
            ->willReturn($value);

        return $an_header;
    }

    public function testBaseMessage()
    {
        $sendGridMailMetadata = new SendGridMailMetadata();

        $headers = $this->getMockBuilder(\Swift_Mime_SimpleHeaderSet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $headers->expects($this->once())
            ->method('getAll')
            ->willReturn([$this->make_header('X-FOO', 'Bar'), $this->make_header('to', 'nobody@email.com')]);

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

        $mail = new Mail('from', 'subject', 'to', 'content');

        $sendGridMailMetadata->addMetadataToMail($mail, $message);

        $replyTo = new ReplyTo('email@example.com');
        $this->assertEquals($replyTo, $mail->getReplyTo());

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
