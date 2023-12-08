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
    /**
     * @var Client
     */
    private $client;

    public function __construct(private Configuration $configuration, private LoggerInterface $logger)
    {
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
        } catch (NumberParseException $exception) {
            $this->logger->warning(
                $exception->getMessage(),
                ['exception' => $exception]
            );

            return $exception->getMessage();
        } catch (ConfigurationException $exception) {
            $message = $exception->getMessage() ?: 'mautic.sms.transport.twilio.not_configured';
            $this->logger->warning(
                $message,
                ['exception' => $exception]
            );

            return $message;
        } catch (TwilioException $exception) {
            $this->logger->warning(
                $exception->getMessage(),
                ['exception' => $exception]
            );

            return $exception->getMessage();
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
