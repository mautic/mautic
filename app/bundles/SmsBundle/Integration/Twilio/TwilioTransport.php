<?php

namespace Mautic\SmsBundle\Integration\Twilio;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioTransport implements TransportInterface
{
    private ?\Twilio\Rest\Client $client = null;

    public function __construct(
        private Configuration $configuration,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $lead, $content)
    {
        $number = $lead->getLeadPhoneNumber();

        if (null === $number) {
            return false;
        }

        try {
            $messagingServiceSid = $this->configuration->getMessagingServiceSid();
            $this->configureClient();

            $this->client->messages->create(
                $this->sanitizeNumber($number),
                $this->createPayload($messagingServiceSid, $content)
            );

            return true;
        } catch (NumberParseException $numberParseException) {
            $this->logger->warning(
                $numberParseException->getMessage(),
                ['exception' => $numberParseException]
            );

            return $numberParseException->getMessage();
        } catch (ConfigurationException $configurationException) {
            $message = $configurationException->getMessage() ?: 'mautic.sms.transport.twilio.not_configured';
            $this->logger->warning(
                $message,
                ['exception' => $configurationException]
            );

            return $message;
        } catch (TwilioException $twilioException) {
            $this->logger->warning(
                $twilioException->getMessage(),
                ['exception' => $twilioException]
            );

            return $twilioException->getMessage();
        }
    }

    /**
     * @param string $number
     *
     * @return string
     *
     * @throws NumberParseException
     */
    private function sanitizeNumber($number)
    {
        $util   = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'US');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * @return mixed[]
     */
    private function createPayload(string $messagingServiceSid, string $content): array
    {
        return [
            'messagingServiceSid' => $messagingServiceSid,
            'body'                => $content,
        ];
    }

    /**
     * @throws ConfigurationException
     */
    private function configureClient(): void
    {
        if ($this->client) {
            // Already configured
            return;
        }

        $this->client = new Client(
            $this->configuration->getAccountSid(),
            $this->configuration->getAuthToken()
        );
    }
}
