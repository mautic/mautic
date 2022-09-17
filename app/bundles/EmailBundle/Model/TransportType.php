<?php

namespace Mautic\EmailBundle\Model;

class TransportType
{
    public const TRANSPORT_ALIAS = 'transport_alias';

    public const FIELD_HOST = 'field_host';

    public const FIELD_PORT = 'field_port';

    public const FIELD_USER = 'field_user';

    public const FIELD_PASSWORD = 'field_password';

    public const FIELD_API_KEY = 'field_api_key';

    /**
     * @var array
     */
    private $transportTypes = [
        'smtp'     => 'mautic.email.config.mailer_transport.smtp',
    ];

    /**
     * @var array
     */
    private $showHost = [
        'smtp',
    ];

    /**
     * @var array
     */
    private $showPort = [
        'smtp',
    ];

    /**
     * @var array
     */
    private $showUser = [
        'smtp',
    ];

    /**
     * @var array
     */
    private $showPassword = [
        'smtp',
    ];

    /**
     * @var array
     */
    private $showApiKey = [
    ];

    /**
     * @return array
     */
    public function getTransportTypes()
    {
        return $this->transportTypes;
    }

    /**
     * @var string[]
     */
    private $transportConfigModels = [];

    /**
     * @return string
     */
    public function getServiceRequiresHost()
    {
        return $this->getString($this->showHost);
    }

    /**
     * @return string
     */
    public function getServiceRequiresPort()
    {
        return $this->getString($this->showPort);
    }

    /**
     * @return string
     */
    public function getServiceRequiresUser()
    {
        return $this->getString($this->showUser);
    }

    /**
     * @return string
     */
    public function getServiceDoNotNeedUser()
    {
        $doNotRequireUser = array_diff($this->transportTypes, $this->showUser);

        return $this->getString($doNotRequireUser);
    }

    public function getServiceDoNotNeedPassword()
    {
        $doNotRequireUser = array_diff($this->transportTypes, $this->showPassword);

        return $this->getString($doNotRequireUser);
    }

    /**
     * @return string
     */
    public function getServiceRequiresPassword()
    {
        return $this->getString($this->showPassword);
    }

    public function isServiceRequiresPassword(): bool
    {
        return ('' !== $this->getServiceRequiresPassword()) ? true : false;
    }

    /**
     * @return string
     */
    public function getServiceRequiresApiKey()
    {
        return $this->getString($this->showApiKey);
    }

    /**
     * @return string
     */
    public function getSmtpService()
    {
        return '"smtp"';
    }

    /**
     * @return string[]
     */
    public function getTrasportConfig(): array
    {
        return $this->transportConfigModels;
    }

    /**
     * @return string
     */
    private function getString(array $services)
    {
        return '"'.implode('","', $services).'"';
    }
}
