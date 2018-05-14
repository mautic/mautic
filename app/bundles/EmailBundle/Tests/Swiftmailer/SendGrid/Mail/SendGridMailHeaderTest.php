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

use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailHeader;
use SendGrid\Mail;

class SendGridMailHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseMessage()
    {
        $sendGridMailHeader = new SendGridMailHeader();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->getMock();

        $header = $this->createMock(\Swift_Mime_Header::class);
        $header->expects($this->once())
            ->method('getFieldName')
            ->willReturn('name');
        $header->expects($this->once())
            ->method('getFieldBodyModel')
            ->willReturn('body');
        $header->expects($this->once())
            ->method('getFieldType')
            ->willReturn(\Swift_Mime_Header::TYPE_TEXT);

        $headerSet = $this->createMock(\Swift_Mime_HeaderSet::class);
        $headerSet->expects($this->once())
            ->method('getAll')
            ->willReturn([$header]);

        $message->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headerSet);

        $mail = new Mail('from', 'subject', 'to', 'content');

        $sendGridMailHeader->addHeadersToMail($mail, $message);

        $this->assertEquals(['name' => 'body'], $mail->getHeaders());
    }
}
