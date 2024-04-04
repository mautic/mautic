<?php

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Transport\MailjetTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

class MailjetTransportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TransportCallback
     */
    private $transportCallback;

    /**
     * @var MailjetTransport
     */
    private $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportCallback = $this->createMock(TransportCallback::class);
        $this->transport         = new MailjetTransport($this->transportCallback);
    }

    public function testWebhookPayloadIsProcessed()
    {
        $this->transportCallback->expects($this->exactly(5))
            ->method('addFailureByHashId')
            ->withConsecutive(
                [$this->equalTo('1'), 'User unsubscribed', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('2'), 'BLOCKED: blocked: blocked', DoNotContact::BOUNCED],
                [$this->equalTo('3'), 'User reported email as spam, source: spam button', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('4'), 'SOFT: bounced: bounced: text of mailjet', DoNotContact::BOUNCED],
                [$this->equalTo('5'), 'HARD: bounced: bounced', DoNotContact::BOUNCED]
            );

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'bounce2@test.com',
                'HARD: bounced: bounce without hash',
                DoNotContact::BOUNCED
            );

        $this->transport->processCallbackRequest($this->getRequestWithPayload());
    }

    public function testSend(): void
    {
        /** @var MockObject|\Swift_Mime_SimpleMessage */
        $message = $this->createMock(\Swift_Mime_SimpleMessage::class);

        $message->method('getReturnPath')->willReturn('return-path-a');

        $this->assertIsInt($this->transport->send($message));
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
    "hard_bounce": false,
    "error_related_to": "bounced",
    "error": "bounced",
    "comment": "text of mailjet"
  }, 
  {
    "event": "bounce",
    "time": 1513975372,
    "MessageID": 0,
    "email": "bounce3@test-test.com",
    "mj_campaign_id": 0,
    "mj_contact_id": 0,
    "customcampaign": "",
    "CustomID": "5-bounce3@test-test.com",
    "Payload": "",
    "blocked": "",
    "hard_bounce": true,
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
