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
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use SendGrid\BccSettings;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\ReplyTo;

class SendGridMailMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testBaseMessage()
    {
        $sendGridMailMetadata = new SendGridMailMetadata();

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

    /**
     * @dataProvider mauticMessageMetadataProvider
     */
    public function testMauticMessageMetadata($metadata)
    {
        $sendGridMailMetadata = new SendGridMailMetadata();

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->any())
            ->method('getMetadata')
            ->with()
            ->willReturn([$metadata]);

        $mail = new Mail('from', 'subject', 'to', 'content');

        $sendGridMailMetadata->addMetadataToMail($mail, $message);

        $args = $mail->getCustomArgs();

        if (empty($metadata['hashId'])) {
            $this->assertFalse(isset($args['hashId']));
        } else {
            $this->assertSame($metadata['hashId'], $args['hashId']);
        }

        if (empty($metadata['emailId'])) {
            $this->assertFalse(isset($args['emailId']));
        } else {
            $this->assertSame($metadata['emailId'], $args['emailId']);
        }

        if (!empty($metadata['source']) && is_array($metadata['source'])) {
            $this->assertSame($metadata['source'][0], $args['channel']);
            $this->assertSame($metadata['source'][1], $args['sourceId']);
        } else {
            $this->assertFalse(isset($args['channel']));
            $this->assertFalse(isset($args['sourceId']));
        }
    }

    public function mauticMessageMetadataProvider()
    {
        return [
            'complete' => [[
                'hashId'  => '6059caf4828b8409852053',
                'emailId' => '1234',
                'source'  => ['email', '321'],
            ]],
            'partial' => [[
                'hashId'  => '',
                'emailId' => '5678',
                'source'  => [],
            ]],
            'empty' => [[]],
        ];
    }
}
