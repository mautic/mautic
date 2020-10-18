<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\Mailgun\Mail;

use Mailgun\Message\MessageBuilder;
use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use  Mautic\EmailBundle\Swiftmailer\Mailgun\Mail\MailgunMailBase;

class MailgunMailBaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider contentTypeProvider
     */
    public function testHtmlMessage($contentType)
    {
        $plainTextMessageHelper = $this->getMockBuilder(PlainTextMessageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailgunMailBase = new MailgunMailBase($plainTextMessageHelper);

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('getFrom')
            ->with()
            ->willReturn(['email@example.com' => 'My name']);

        $message->expects($this->once())
            ->method('getSubject')
            ->with()
            ->willReturn('My subject');

        $message->expects($this->once())
            ->method('getContentType')
            ->with()
            ->willReturn($contentType);

        $message->expects($this->once())
            ->method('getBody')
            ->with()
            ->willReturn('HTML body');

        $plainTextMessageHelper->expects($this->once())
            ->method('getPlainTextFromMessageNotStatic')
            ->with($message)
            ->willReturn('Plain text');

        $mail = $mailgunMailBase->getMailgunMail($message);
        $data = $mail->getMessage();

        $this->assertEquals($data['from'][0], '"My name" <email@example.com>');

        $this->assertSame($data['html'], 'HTML body');
        $this->assertSame($data['text'], 'Plain text');
    }

    public function contentTypeProvider()
    {
        return [
            ['text/html'],
            ['multipart/alternative'],
        ];
    }

    public function testPlainTextMessage()
    {
        $plainTextMessageHelper = $this->getMockBuilder(PlainTextMessageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailgunMailBase = new MailgunMailBase($plainTextMessageHelper);

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('getFrom')
            ->with()
            ->willReturn(['email@example.com' => 'My name']);

        $message->expects($this->once())
            ->method('getSubject')
            ->with()
            ->willReturn('My subject');

        $message->expects($this->exactly(2))
            ->method('getContentType')
            ->with()
            ->willReturn('text/plain');

        $message->expects($this->once())
            ->method('getBody')
            ->with()
            ->willReturn('Plain text');

        $plainTextMessageHelper->expects($this->never())
            ->method('getPlainTextFromMessageNotStatic');

        $mail = $mailgunMailBase->getMailgunMail($message);
        $data = $mail->getMessage();

        $this->assertSame($data['text'], 'Plain text');
    }

    public function testEmptyPlainTextMessage()
    {
        $plainTextMessageHelper = $this->getMockBuilder(PlainTextMessageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailgunMailBase = new MailgunMailBase($plainTextMessageHelper);

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('getFrom')
            ->with()
            ->willReturn(['email@example.com' => 'My name']);

        $message->expects($this->once())
            ->method('getSubject')
            ->with()
            ->willReturn('My subject');

        $message->expects($this->once())
            ->method('getContentType')
            ->with()
            ->willReturn('text/html');

        $message->expects($this->once())
            ->method('getBody')
            ->with()
            ->willReturn('HTML body');

        $plainTextMessageHelper->expects($this->once())
            ->method('getPlainTextFromMessageNotStatic')
            ->with($message)
            ->willReturn('');

        $mail = $mailgunMailBase->getMailgunMail($message);
        $data = $mail->getMessage();
        $this->assertEquals($data['html'], 'HTML body');
    }
}
