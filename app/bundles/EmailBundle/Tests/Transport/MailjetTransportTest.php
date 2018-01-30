<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Transport\MailjetTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

class MailjetTransportTest extends \PHPUnit_Framework_TestCase
{
    public function testWebhookPayloadIsProcessed()
    {
        $transportCallback = $this->getMockBuilder(TransportCallback::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transportCallback->expects($this->exactly(4))
            ->method('addFailureByHashId')
            ->withConsecutive(
                [$this->equalTo('1'), 'User unsubscribed', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('2'), 'blocked: blocked', DoNotContact::BOUNCED],
                [$this->equalTo('3'), 'User reported email as spam, source: spam button', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('4'), 'bounced: bounced', DoNotContact::BOUNCED]
            );

        $transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'bounce2@test.com',
                'bounced: bounce without hash',
                DoNotContact::BOUNCED
            );

        $transport = new MailjetTransport($transportCallback);

        $transport->processCallbackRequest($this->getRequestWithPayload());
    }

    /**
     * @return Request
     */
    private function getRequestWithPayload()
    {
        $json = <<<JSON
    [
  {
    "event": "unsub",
    "time": 1513975381,
    "MessageID": 0,
    "email": "unsub@test.com",
    "mj_campaign_id": 0,
    "mj_contact_id": 0,
    "customcampaign": "",
    "CustomID": "1-unsub@test.com",
    "Payload": "",
    "mj_list_id": "",
    "ip": "",
    "geo": "",
    "agent": ""
  },
  {
    "event": "blocked",
    "time": 1513975379,
    "MessageID": 0,
    "email": "blocked@test.com",
    "mj_campaign_id": 0,
    "mj_contact_id": 0,
    "customcampaign": "",
    "CustomID": "2-blocked@test.com",
    "Payload": "",
    "error_related_to": "blocked",
    "error": "blocked"
  },
  {
    "event": "spam",
    "time": 1513975376,
    "MessageID": 0,
    "email": "spam@test.com",
    "mj_campaign_id": 0,
    "mj_contact_id": 0,
    "customcampaign": "",
    "CustomID": "3-spam@test.com",
    "Payload": "",
    "source": "spam button"
  },
  {
    "event": "bounce",
    "time": 1513975374,
    "MessageID": 0,
    "email": "bounce@test.com",
    "mj_campaign_id": 0,
    "mj_contact_id": 0,
    "customcampaign": "",
    "CustomID": "4-bounce@test.com",
    "Payload": "",
    "blocked": "",
    "hard_bounce": "",
    "error_related_to": "bounced",
    "error": "bounced"
  },
  {
    "event": "bounce",
    "time": 1513975374,
    "MessageID": 0,
    "email": "bounce2@test.com",
    "mj_campaign_id": 0,
    "mj_contact_id": 0,
    "customcampaign": "",
    "CustomID": "",
    "Payload": "",
    "blocked": "",
    "hard_bounce": "",
    "error_related_to": "bounced",
    "error": "bounce without hash"
  }
]
JSON;

        return new Request([], [], [], [], [], [], $json);
    }
}
