<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\ResponseItem;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\ResponseItems;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

class ResponseItemsTest extends \PHPUnit_Framework_TestCase
{
    public function testResponseItems()
    {
        $payload = [
            [
                'email'         => 'example1@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'processed',
                'category'      => 'cat facts',
                'sg_event_id'   => 'glU3g7DJ-O__EQ6VLGucXg==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
            ],
            [
                'email'         => 'example2@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'deferred',
                'category'      => 'cat facts',
                'sg_event_id'   => '3OrxiMmQivb5zcKvsmVu3w==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'response'      => '400 try again later',
                'attempt'       => '5',
            ],
            [
                'email'         => 'example3@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'delivered',
                'category'      => 'cat facts',
                'sg_event_id'   => '5651o54fFucWOslhS0KjIw==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'response'      => '250 OK',
            ],
            [
                'email'         => 'example4@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'open',
                'category'      => 'cat facts',
                'sg_event_id'   => 'u2eeaWXQNEZWfai-rKOEXg==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'useragent'     => 'Mozilla/4.0 [compatible; MSIE 6.1; Windows XP; .NET CLR 1.1.4322; .NET CLR 2.0.50727]',
                'ip'            => '255.255.255.255',
            ],
            [
                'email'         => 'example5@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'click',
                'category'      => 'cat facts',
                'sg_event_id'   => 'cnlAXAv_JrVIKBxfIbzJYA==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'useragent'     => 'Mozilla/4.0 [compatible; MSIE 6.1; Windows XP; .NET CLR 1.1.4322; .NET CLR 2.0.50727]',
                'ip'            => '255.255.255.255',
                'url'           => 'http://www.sendgrid.com/',
            ],
            [
                'email'         => 'example6@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'bounce',
                'category'      => 'cat facts',
                'sg_event_id'   => '0zPC-is_ZeC7f6XD7KNElw==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'reason'        => '500 unknown recipient',
                'status'        => '5.0.0',
            ],
            [
                'email'         => 'example7@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'dropped',
                'category'      => 'cat facts',
                'sg_event_id'   => 'vLeH071SCk_wqaw_ieKp2w==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'reason'        => 'Bounced Address',
                'status'        => '5.0.0',
            ],
            [
                'email'         => 'example8@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'spamreport',
                'category'      => 'cat facts',
                'sg_event_id'   => 'wiTrG1ePeFr3M-E2eTsd3Q==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
            ],
            [
                'email'         => 'example9@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'unsubscribe',
                'category'      => 'cat facts',
                'sg_event_id'   => 'ADu-7OmUtgyFrfDtEto5zw==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
            ],
            [
                'email'         => 'example10@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'group_unsubscribe',
                'category'      => 'cat facts',
                'sg_event_id'   => '7HlYVEA2Ff6VGY7KmaT5LQ==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'useragent'     => 'Mozilla/4.0 [compatible; MSIE 6.1; Windows XP; .NET CLR 1.1.4322; .NET CLR 2.0.50727]',
                'ip'            => '255.255.255.255',
                'url'           => 'http://www.sendgrid.com/',
                'asm_group_id'  => '10',
            ],
            [
                'email'         => 'example11@test.com',
                'timestamp'     => '1512130989',
                'smtp-id'       => '<14c5d75ce93.dfd.64b469@ismtpd-555>',
                'event'         => 'group_resubscribe',
                'category'      => 'cat facts',
                'sg_event_id'   => 'XPlckPemAAmRpG9C-JwN1w==',
                'sg_message_id' => '14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0',
                'useragent'     => 'Mozilla/4.0 [compatible; MSIE 6.1; Windows XP; .NET CLR 1.1.4322; .NET CLR 2.0.50727]',
                'ip'            => '255.255.255.255',
                'url'           => 'http://www.sendgrid.com/',
                'asm_group_id'  => '10',
            ],
            [
            ],
        ];

        $request = new Request(['query'], $payload);

        $responseItems = new ResponseItems($request);

        $responseItem = $responseItems->current();
        $this->checkResponseItem($responseItem, 'example6@test.com', '500 unknown recipient', DoNotContact::BOUNCED);

        $responseItems->next();
        $responseItem = $responseItems->current();
        $this->checkResponseItem($responseItem, 'example7@test.com', 'Bounced Address', DoNotContact::BOUNCED);

        $responseItems->next();
        $responseItem = $responseItems->current();
        $this->checkResponseItem($responseItem, 'example8@test.com', null, DoNotContact::BOUNCED);

        $responseItems->next();
        $responseItem = $responseItems->current();
        $this->checkResponseItem($responseItem, 'example9@test.com', null, DoNotContact::UNSUBSCRIBED);

        $responseItems->next();
        $responseItem = $responseItems->current();
        $this->checkResponseItem($responseItem, 'example10@test.com', null, DoNotContact::UNSUBSCRIBED);

        $responseItems->next();
        $this->assertFalse($responseItems->valid());
    }

    private function checkResponseItem(ResponseItem $responseItem, $email, $reason, $DncReason)
    {
        $this->assertSame($email, $responseItem->getEmail());
        $this->assertSame($reason, $responseItem->getReason());
        $this->assertSame($DncReason, $responseItem->getDncReason());
    }
}
