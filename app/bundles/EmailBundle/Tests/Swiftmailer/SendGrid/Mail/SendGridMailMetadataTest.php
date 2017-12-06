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
use SendGrid\BccSettings;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\ReplyTo;

class SendGridMailMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseMessage()
    {
        $sendGridMailMetadata = new SendGridMailMetadata();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->getMock();

        $message->expects($this->exactly(2))
            ->method('getReplyTo')
            ->with()
            ->willReturn(['email@example.com' => 'email@example.com']);

        $message->expects($this->exactly(2))
            ->method('getBcc')
            ->with()
            ->willReturn(['bcc@example.com' => 'bcc@example.com']);

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
        dump($bccSettings->getEmail());

        $this->assertSame('bcc@example.com', $bccSettings->getEmail());
        $this->assertTrue($bccSettings->getEnable());
    }
}
