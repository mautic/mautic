<?php

namespace Mautic\SmsBundle\Integration\Twilio;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Twilio\Exceptions\ConfigurationException;

class Configuration
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
        private IntegrationHelper $integrationHelper,
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

        $integration = $this->integrationHelper->getIntegrationObject('Twilio');

        if (!$integration || !$integration->getIntegrationSettings()->getIsPublished()) {
            throw new ConfigurationException();
        }

        $this->messagingServiceSid = $integration->getIntegrationSettings()->getFeatureSettings()['messaging_service_sid'];
        if (empty($this->messagingServiceSid)) {
            throw new ConfigurationException();
        }

        $keys = $integration->getDecryptedApiKeys();
        if (empty($keys['username']) || empty($keys['password'])) {
            throw new ConfigurationException();
        }

        $this->accountSid = $keys['username'];
        $this->authToken  = $keys['password'];
    }
}
