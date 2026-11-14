<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Integration\Twilio;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use Twilio\Exceptions\ConfigurationException;

final class Configuration
{
    /**
     * @var string
     */
    private $messagingServiceSid;

    /**
     * @var string
     */
    private $accountSid;

    /**
     * @var string
     */
    private $authToken;

    public function __construct(
        private readonly TwilioIntegration $integration,
        private readonly IntegrationsHelper $integrationsHelper,
    ) {
    }

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getMessagingServiceSid()
    {
        $this->setConfiguration();

        return $this->messagingServiceSid;
    }

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getAccountSid()
    {
        $this->setConfiguration();

        return $this->accountSid;
    }

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getAuthToken()
    {
        $this->setConfiguration();

        return $this->authToken;
    }

    /**
     * @throws ConfigurationException
     */
    private function setConfiguration(): void
    {
        if ($this->accountSid) {
            return;
        }

        try {
            // loads the settings and decrypts the API keys
            $settings = $this->integrationsHelper->getIntegrationConfiguration($this->integration);
        } catch (IntegrationNotFoundException) {
            throw new ConfigurationException();
        }

        if (!$settings->getIsPublished()) {
            throw new ConfigurationException();
        }

        $this->messagingServiceSid = $this->integration->getIntegrationFeatureSettings()['messaging_service_sid'] ?? null;
        if (empty($this->messagingServiceSid)) {
            throw new ConfigurationException();
        }

        $keys = $settings->getApiKeys();
        if (empty($keys['username']) || empty($keys['password'])) {
            throw new ConfigurationException();
        }

        $this->accountSid = $keys['username'];
        $this->authToken  = $keys['password'];
    }
}
