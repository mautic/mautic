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
use Mautic\EmailBundle\Swiftmailer\Transport\PostalTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class PostalTransportTest extends \PHPUnit\Framework\TestCase
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

        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->transportCallback = $this->createMock(TransportCallback::class);
        $this->transport         = new PostalTransport($this->translator, $this->logger, $this->transportCallback);
    }

    public function testWebhookPayloadIsProcessed()
    {

        $this->translator->method('trans')
            ->willReturnCallback(
                function ($key) {
                    return $key;
                }
            );

        $this->transportCallback->expects($this->exactly(2))
            ->method('addFailureByAddress')
            ->withConsecutive(
                ['test@example.com', 'mautic.email.bounce.reason.hard_bounce', DoNotContact::BOUNCED],
                ['test@example.com', 'mautic.email.bounce.reason.other', DoNotContact::BOUNCED]
            );

        $this->transport->processCallbackRequest($this->getRequestWithPayloadBounced());

        $this->transport->processCallbackRequest($this->getRequestWithPayloadFailed());
    }

    /**
     * @return Request
     */
    private function getRequestWithPayloadBounced()
    {
        $json = <<<JSON
  {
    "event": "MessageBounced",
    "payload":{
      "original_message":{
        "id":12345,
        "token":"abcdef123",
        "direction":"outgoing",
        "message_id":"5817a64332f44_4ec93ff59e79d154565eb@app34.mail",
        "to":"test@example.com",
        "from":"sales@awesomeapp.com",
        "subject":"Welcome to AwesomeApp",
        "timestamp":1477945177.12994,
        "spam_status":"NotSpam",
        "tag":"welcome"
      },
      "bounce":{
        "id":12347,
        "token":"abcdef124",
        "direction":"incoming",
        "message_id":"5817a64332f44_4ec93ff59e79d154565eb@someserver.com",
        "to":"abcde@psrp.postal.yourdomain.com",
        "from":"postmaster@someserver.com",
        "subject":"Delivery Error",
        "timestamp":1477945179.12994,
        "spam_status":"NotSpam",
        "tag":null
      }
    }
  }
JSON;

        return new Request([], [], [], [], [], [], $json);
    }

    /**
     * @return Request
     */
    private function getRequestWithPayloadFailed()
    {
        $json = <<<JSON
  {
    "event": "MessageDeliveryFailed",
    "payload":{
      "message":{
        "id":12345,
        "token":"abcdef123",
        "direction":"outgoing",
        "message_id":"5817a64332f44_4ec93ff59e79d154565eb@app34.mail",
        "to":"test@example.com",
        "from":"sales@awesomeapp.com",
        "subject":"Welcome to AwesomeApp",
        "timestamp":1477945177.12994,
        "spam_status":"NotSpam",
        "tag":"welcome"
      }
    }
  }
JSON;

        return new Request([], [], [], [], [], [], $json);
    }
}