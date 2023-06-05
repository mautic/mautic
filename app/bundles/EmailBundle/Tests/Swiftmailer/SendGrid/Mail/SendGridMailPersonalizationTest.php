<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use SendGrid\Mail\Content;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\Subject;
use SendGrid\Mail\To;

class SendGridMailPersonalizationTest extends \PHPUnit\Framework\TestCase
{
    public function testNotMauticMessage()
    {
        $sendGridMailPersonalization = new SendGridMailPersonalization();

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->never())
            ->method('getCc');

        $to = [
            'info1@example.com' => 'Name 1',
        ];
        $message->expects($this->once())
            ->method('getTo')
            ->willReturn($to);

        //        $mail                  = new Mail('from', 'subject', 'to', 'content');
        $mail = new Mail();

        $sendGridMailPersonalization->addPersonalizedDataToMail($mail, $message);

        $personalization = $mail->getPersonalizations();
        $this->assertCount(2, $personalization);

        /**
         * @var Personalization
         */
        $personalization = $personalization[1];
        $tos             = $personalization->getTos();
        $to              = $tos[0];
        $toExpected      = new To('info1@example.com', 'Name 1');
        $this->assertEquals($toExpected, $to);
    }

    public function testPersonalization()
    {
        $sendGridMailPersonalization = new SendGridMailPersonalization();

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = new Mail();

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
        $this->assertCount(3, $personalization);

        $ccExpected = new To('cc@example.com', 'Name cc');

        /**
         * @var Personalization
         * @var Personalization $personalization2
         */
        $personalization1 = $personalization[1];
        $tos              = $personalization1->getTos();
        $to               = $tos[0];
        $toExpected       = new To('info1@example.com', 'Name 1');
        $this->assertEquals($toExpected, $to);

        $ccs = $personalization1->getCcs();
        $cc  = $ccs[0];
        $this->assertEquals($ccExpected, $cc);

        $personalization2 = $personalization[2];
        $tos              = $personalization2->getTos();
        $to               = $tos[0];
        $toExpected       = new To('info2@example.com', 'Name 2');
        $this->assertEquals($toExpected, $to);

        $ccs = $personalization2->getCcs();
        $cc  = $ccs[0];
        $this->assertEquals($ccExpected, $cc);
    }
}
