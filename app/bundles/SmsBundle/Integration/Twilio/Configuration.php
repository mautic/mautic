<?php

namespace Mautic\SmsBundle\Integration\Twilio;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Twilio\Exceptions\ConfigurationException;

class Configuration
{
    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var string
     */
    private $sendingPhoneNumber;

    /**
     * @var string
     */
    private $accountSid;

    /**
     * @var string
     */
    private $authToken;

    /**
     * Configuration constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return string
     *
     * @throws ConfigurationException
     */
    public function getSendingNumber()
    {
        $this->setConfiguration();

        return $this->sendingPhoneNumber;
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
    private function setConfiguration()
    {
        if ($this->accountSid) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject('Twilio');

        if (!$integration || !$integration->getIntegrationSettings()->getIsPublished()) {
            throw new ConfigurationException();
        }

        $this->sendingPhoneNumber = $integration->getIntegrationSettings()->getFeatureSettings()['sending_phone_number'];
        if (empty($this->sendingPhoneNumber)) {
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
