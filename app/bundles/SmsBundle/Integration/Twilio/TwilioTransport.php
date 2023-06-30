<?php

namespace Mautic\SmsBundle\Integration\Twilio;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\MMSTransportInterface;
use Mautic\SmsBundle\Sms\TransportInterface;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioTransport implements TransportInterface
{
    private Configuration $configuration;

    private LoggerInterface $logger;

    private Client $client;

    /**
     * TwilioTransport constructor.
     */
    public function __construct(Configuration $configuration, LoggerInterface $logger)
    {
        $this->logger        = $logger;
        $this->configuration = $configuration;
    }

    /**
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $lead, $content)
    {
        return $this->sendMessage($lead, $content);
    }

    /**
     * @param array<mixed> $media
     *
     * @return bool|string
     */
    public function sendMms(Lead $lead, string $content, array $media)
    {
        return $this->sendMessage($lead, $content, $media);
    }

    /**
     * @param string       $content
     * @param array<mixed> $media
     *
     * @return bool|string
     */
    private function sendMessage(Lead $lead, $content, array $media = [])
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
                $this->createPayload($messagingServiceSid, $content, $media)
            );

            return true;
        } catch (NumberParseException $exception) {
            $this->logger->warning(
                $exception->getMessage(),
                ['exception' => $exception]
            );

            return $exception->getMessage();
        } catch (ConfigurationException $exception) {
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'mautic.sms.transport.twilio.not_configured';
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
     * @param mixed[] $media
     *
     * @return mixed[]
     */
    private function createPayload(string $messagingServiceSid, string $content, array $media): array
    {
        $payload = [
            'messagingServiceSid' => $messagingServiceSid,
            'body'                => $content,
        ];
        if (!empty($media)) {
            $payload['mediaUrl'] = $media;
        }

        return $payload;
    }

    /**
     * @throws ConfigurationException
     */
    private function configureClient()
    {
        if ($this->client) {
            // Already configured
            return;
        }

        $this->client              = new Client(
            $this->configuration->getAccountSid(),
            $this->configuration->getAuthToken()
        );
    }
}
