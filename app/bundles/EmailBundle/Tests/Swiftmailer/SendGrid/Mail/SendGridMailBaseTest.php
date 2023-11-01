<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use SendGrid\Content;
use SendGrid\Email;

class SendGridMailBaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider contentTypeProvider
     */
    public function testHtmlMessage($contentType)
    {
        $plainTextMessageHelper = $this->getMockBuilder(PlainTextMessageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailBase = new SendGridMailBase($plainTextMessageHelper);

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

        $mail = $sendGridMailBase->getSendGridMail($message);

        $personalizations = $mail->getPersonalizations();
        $this->assertSame([], $personalizations);

        $from = new Email('My name', 'email@example.com');
        $this->assertEquals($from, $mail->getFrom());

        $this->assertSame('My subject', $mail->getSubject());

        $contents = $mail->getContents();
        $this->assertCount(2, $contents);

        $plainText   = new Content('text/plain', 'Plain text');
        $htmlContent = new Content('text/html', 'HTML body');
        $this->assertEquals($plainText, $contents[0]);
        $this->assertEquals($htmlContent, $contents[1]);
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

        $sendGridMailBase = new SendGridMailBase($plainTextMessageHelper);

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

        $mail = $sendGridMailBase->getSendGridMail($message);

        $personalizations = $mail->getPersonalizations();
        $this->assertSame([], $personalizations);

        $from = new Email('My name', 'email@example.com');
        $this->assertEquals($from, $mail->getFrom());

        $this->assertSame('My subject', $mail->getSubject());

        $contents = $mail->getContents();
        $this->assertCount(1, $contents);

        $plainText   = new Content('text/plain', 'Plain text');
        $this->assertEquals($plainText, $contents[0]);
    }

    public function testEmptyPlainTextMessage()
    {
        $plainTextMessageHelper = $this->getMockBuilder(PlainTextMessageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailBase = new SendGridMailBase($plainTextMessageHelper);

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

        $mail = $sendGridMailBase->getSendGridMail($message);

        $personalizations = $mail->getPersonalizations();
        $this->assertSame([], $personalizations);

        $from = new Email('My name', 'email@example.com');
        $this->assertEquals($from, $mail->getFrom());

        $this->assertSame('My subject', $mail->getSubject());

        $contents = $mail->getContents();
        $this->assertCount(1, $contents);

        $content   = new Content('text/html', 'HTML body');
        $this->assertEquals($content, $contents[0]);
    }
}
