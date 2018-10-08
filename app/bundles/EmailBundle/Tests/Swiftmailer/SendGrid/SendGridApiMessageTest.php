<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Event\GetMailMessageEvent;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridMailEvents;
use SendGrid\Mail;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SendGridApiMessageTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMail()
    {
        $sendGridMailBase = $this->getMockBuilder(SendGridMailBase::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailPersonalization = $this->getMockBuilder(SendGridMailPersonalization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailMetadata = $this->getMockBuilder(SendGridMailMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailAttachment = $this->getMockBuilder(SendGridMailAttachment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcher::class);

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder(\Swift_Mime_SimpleMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = new SendGridApiMessage($sendGridMailBase, $sendGridMailPersonalization, $sendGridMailMetadata, $sendGridMailAttachment, $dispatcher);

        $sendGridMailBase->expects($this->once())
            ->method('getSendGridMail')
            ->with($message)
            ->willReturn($mail);

        $sendGridMailPersonalization->expects($this->once())
            ->method('addPersonalizedDataToMail')
            ->with($mail, $message);

        $sendGridMailMetadata->expects($this->once())
            ->method('addMetadataToMail')
            ->with($mail, $message);

        $sendGridMailAttachment->expects($this->once())
            ->method('addAttachmentsToMail')
            ->with($mail, $message);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(SendGridMailEvents::GET_MAIL_MESSAGE),
                $this->isInstanceOf(GetMailMessageEvent::class)
            );

        $result = $sendGridApiMessage->getMessage($message);

        $this->assertSame($mail, $result);
    }
}
