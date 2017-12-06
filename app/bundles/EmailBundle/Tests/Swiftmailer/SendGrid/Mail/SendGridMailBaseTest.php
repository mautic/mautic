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

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use SendGrid\Content;
use SendGrid\Email;

class SendGridMailBaseTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseMessage()
    {
        $plainTextMassageHelper = $this->getMockBuilder(PlainTextMassageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailBase = new SendGridMailBase($plainTextMassageHelper);

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
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
            ->method('getBody')
            ->with()
            ->willReturn('HTML body');

        $message->expects($this->once())
            ->method('getBody')
            ->with()
            ->willReturn('HTML body');

        $plainTextMassageHelper->expects($this->once())
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
}
