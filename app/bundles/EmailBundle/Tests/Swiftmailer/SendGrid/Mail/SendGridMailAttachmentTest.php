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

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use SendGrid\Mail;

class SendGridMailAttachmentTest extends \PHPUnit_Framework_TestCase
{
    public function testNotMauticMessage()
    {
        $sendGridMailAttachment = new SendGridMailAttachment();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
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
