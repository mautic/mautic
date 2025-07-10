<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Mautic\SmsBundle\Integration\Twilio\TwilioTransport;
use Monolog\Logger;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TwilioTransportTest extends TestCase
{
    private TwilioTransport $twilioTransport;

    /**
     * @var MockObject&Logger
     */
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger      = $this->createMock(Logger::class);
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $configuration     = new Configuration($integrationHelper);

        $this->twilioTransport = new TwilioTransport($configuration, $this->logger);
    }

    public function testSendSMS(): void
    {
        $lead = new Lead();
        $lead->setMobile('123456');
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('mautic.sms.transport.twilio.not_configured');

        $this->twilioTransport->sendSms($lead, 'some_content');
    }

    public function testCreatePayload(): void
    {
        $reflection = new \ReflectionClass($this->twilioTransport::class);
        $method     = $reflection->getMethod('createPayload');
        $method->setAccessible(true);

        $payload = ['messagingServiceSid' => 'MS1234', 'body' => 'some_content'];

        $result = $method->invokeArgs($this->twilioTransport, array_values($payload));
        Assert::assertSame($payload, $result);
    }
}
