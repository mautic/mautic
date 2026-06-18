<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Sms;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;
use Mautic\SmsBundle\Integration\Twilio\TwilioTransport;
use Mautic\SmsBundle\Sms\BulkTransportInterface;
use Mautic\SmsBundle\Sms\MMSTransportInterface;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\Sms\TransportInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class TransportChainTest extends MauticMysqlTestCase
{
    private TransportChain $transportChain;

    private MockObject&TransportInterface $twilioTransport;

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on
     * @param string $methodName Method name to call
     * @param array  $parameters array of parameters to pass into method
     *
     * @return mixed method return
     *
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object::class);
        $method     = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $parameters);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportChain = new TransportChain(
            'mautic.test.twilio.mock',
            static::getContainer()->get('mautic.helper.integration')
        );

        $this->twilioTransport = $this->createMock(TwilioTransport::class);

        $this->twilioTransport
            ->method('sendSMS')
            ->willReturn('lol');
    }

    public function testAddTransport(): void
    {
        $count = count($this->transportChain->getTransports());

        $this->transportChain->addTransport('mautic.transport.test', static::getContainer()->get('mautic.sms.twilio.transport'), 'mautic.transport.test', 'Twilio');

        $this->assertCount($count + 1, $this->transportChain->getTransports());
    }

    public function testSendSms(): void
    {
        $this->testAddTransport();

        $this->transportChain->addTransport('mautic.test.twilio.mock', $this->twilioTransport, 'mautic.test.twilio.mock', 'Twilio');

        $lead = new Lead();
        $lead->setMobile('+123456789');

        try {
            $this->transportChain->sendSms($lead, 'Yeah');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->assertEquals('Primary SMS transport is not enabled', $message);
        }
    }

    public function testSendBatchSms(): void
    {
        $bulkSmsTransport = new class implements BulkTransportInterface {
            public function sendBatchSms(RecipientCollection $collection, string $content): RecipientCollection
            {
                foreach ($collection as &$recipient) {
                    $recipient->setResult(true);
                }

                return $collection;
            }

            public function sendSms(Lead $lead, $content): bool
            {
                return true;
            }
        };
        $this->createDataAndAssertSendMessage($bulkSmsTransport);
    }

    public function testSendMessage(): void
    {
        $mmsTransport = new class implements TransportInterface, MMSTransportInterface {
            public function sendMms(Lead $lead, string $content, array $media): bool|string
            {
                return true;
            }

            public function sendSms(Lead $lead, $content): bool
            {
                return true;
            }
        };
        $this->createDataAndAssertSendMessage($mmsTransport);
    }

    private function createDataAndAssertSendMessage(TransportInterface $transport): void
    {
        $transportChain = new class('mautic.test.bulktwilio.mock', self::getContainer()->get('mautic.helper.integration')) extends TransportChain {
            public function getEnabledTransports(): array
            {
                $transports = $this->getTransports();

                return array_map(fn ($v) => $v['service'], $transports);
            }
        };

        $transportChain->addTransport('mautic.test.bulktwilio.mock', $transport, 'mautic.test.bulktwilio.mock', 'BulkTwilio');

        $lead1 = new Lead();
        $lead1->setMobile('+123456789');
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setMobile('+123456790');
        $lead2->setId(2);

        $recipientCollection = new RecipientCollection(new Sms());
        $recipientCollection->append(new SmsRecipientDTO($lead1, [], 'Yeah'));
        $recipientCollection->append(new SmsRecipientDTO($lead2, [], 'Yeah'));

        if ($transport instanceof MMSTransportInterface) {
            $recipientCollection = $transportChain->sendMMS($recipientCollection, ['test.png']);
        } elseif ($transport instanceof BulkTransportInterface) {
            $recipientCollection = $transportChain->sendBatchSms($recipientCollection, 'Yeah');
        }

        $sentCount = 0;
        foreach ($recipientCollection as $recipient) {
            if ($recipient->getResult()) {
                ++$sentCount;
            }
        }

        self::assertEquals(2, $sentCount);
    }
}
