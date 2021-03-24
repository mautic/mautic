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
            ->willReturn($metadata);

        $mail = new Mail('from', 'subject', 'to', 'content');

        $sendGridMailMetadata->addMetadataToMail($mail, $message);

        $args = $mail->getCustomArgs();

        // Args come from the first entry in the metadata array. An empty
        // array is forced here to facilitate making the same assertions on
        // all of the test cases.
        $expected = empty($metadata) ? [] : reset($metadata);

        if (empty($expected['hashId'])) {
            $this->assertFalse(isset($args['hashId']));
        } else {
            $this->assertSame($expected['hashId'], $args['hashId']);
        }

        if (empty($expected['emailId'])) {
            $this->assertFalse(isset($args['emailId']));
        } else {
            $this->assertSame($expected['emailId'], $args['emailId']);
        }

        if (!empty($expected['source']) && is_array($expected['source'])) {
            $this->assertSame($expected['source'][0], $args['channel']);
            $this->assertSame($expected['source'][1], $args['sourceId']);
        } else {
            $this->assertFalse(isset($args['channel']));
            $this->assertFalse(isset($args['sourceId']));
        }
    }

    public function mauticMessageMetadataProvider()
    {
        return [
            // All four args are populated
            'complete' => [[[
                'hashId'  => '6059caf4828b8409852053',
                'emailId' => '1234',
                'source'  => ['email', '321'],
            ]]],

            // Only emailId is populated because the other two metadata values
            // are empty.
            'partial' => [[[
                'hashId'  => '',
                'emailId' => '5678',
                'source'  => [],
            ]]],

            // The metadata array isn't empty(), but its only entry is.
            'empty entry' => [[[]]],

            // The metadata array is empty, causing early return from
            // addMauticMessageMetadataToMail().
            'empty metadata' => [[]],
        ];
    }
}
