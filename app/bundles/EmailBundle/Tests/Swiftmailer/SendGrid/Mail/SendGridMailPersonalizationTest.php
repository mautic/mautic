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
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\Personalization;

class SendGridMailPersonalizationTest extends \PHPUnit_Framework_TestCase
{
    public function testNotMauticMessage()
    {
        $sendGridMailPersonalization = new SendGridMailPersonalization();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->getMock();

        $message->expects($this->never())
            ->method('getCc');

        $to = [
            'info1@example.com' => 'Name 1',
        ];
        $message->expects($this->once())
            ->method('getTo')
            ->willReturn($to);

        $mail                  = new Mail('from', 'subject', 'to', 'content');
        $mail->personalization = [];

        $sendGridMailPersonalization->addPersonalizedDataToMail($mail, $message);

        $personalization = $mail->getPersonalizations();
        $this->assertCount(1, $personalization);

        /**
         * @var Personalization
         */
        $personalization = $personalization[0];
        $tos             = $personalization->getTos();
        $to              = $tos[0];
        $toExpected      = new Email('Name 1', 'info1@example.com');
        $this->assertEquals($toExpected, $to);
    }

    public function testPersonalization()
    {
        $sendGridMailPersonalization = new SendGridMailPersonalization();

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail                  = new Mail('from', 'subject', 'to', 'content');
        $mail->personalization = [];

        $to = [
            'info1@example.com' => 'Name 1',
            'info2@example.com' => 'Name 2',
        ];
        $cc = [
            'cc@example.com' => 'Name cc',
        ];
        $metadata = [
            'info1@example.com' => ['tokens' => []],
            'info2@example.com' => ['tokens' => []],
        ];

        $message->expects($this->once())
            ->method('getTo')
            ->willReturn($to);

        $message->expects($this->once())
            ->method('getCc')
            ->willReturn($cc);

        $message->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $sendGridMailPersonalization->addPersonalizedDataToMail($mail, $message);

        $personalization = $mail->getPersonalizations();
        $this->assertCount(2, $personalization);

        $ccExpected = new Email('Name cc', 'cc@example.com');

        /**
         * @var Personalization
         * @var Personalization $personalization2
         */
        $personalization1 = $personalization[0];
        $tos              = $personalization1->getTos();
        $to               = $tos[0];
        $toExpected       = new Email('Name 1', 'info1@example.com');
        $this->assertEquals($toExpected, $to);

        $ccs = $personalization1->getCcs();
        $cc  = $ccs[0];
        $this->assertEquals($ccExpected, $cc);

        $personalization2 = $personalization[1];
        $tos              = $personalization2->getTos();
        $to               = $tos[0];
        $toExpected       = new Email('Name 2', 'info2@example.com');
        $this->assertEquals($toExpected, $to);

        $ccs = $personalization2->getCcs();
        $cc  = $ccs[0];
        $this->assertEquals($ccExpected, $cc);
    }
}
