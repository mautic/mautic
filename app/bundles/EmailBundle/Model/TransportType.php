<?php

namespace Mautic\EmailBundle\Model;

class TransportType
{
    public const TRANSPORT_ALIAS = 'transport_alias';

    public const FIELD_HOST        = 'field_host';
    public const FIELD_PORT        = 'field_port';
    public const FIELD_USER        = 'field_user';
    public const FIELD_PASSWORD    = 'field_password';
    public const FIELD_API_KEY     = 'field_api_key';
    public const TRANSPORT_OPTIONS = 'transport_options';
    public const DSN_CONVERTOR     = 'transport_dsn';

    /**
     * @var array
     */
    private $transportTypes = [];

    /**
     * @var array
     */
    private $showHost = [];

    /**
     * @var array
     */
    private $showPort = [];

    /**
     * @var array
     */
    private $showUser = [];

    /**
     * @var array
     */
    private $showPassword = [];

    /**
     * @var array
     */
    private $showApiKey = [];

    /**
     * @var array<array<string>>
     */
    private $transportConfigModels = [];

    /**
     * @var string[]
     */
    private $transportDsnConvertors = [];

    /**
     * @return array
     */
    public function getTransportTypes()
    {
        return $this->transportTypes;
    }

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
        $doNotRequirePassword = array_diff($this->transportTypes, $this->showPassword);

        return $this->getString($doNotRequirePassword);
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
     * @return array<array<string>>
     */
    public function getTrasportConfig(): array
    {
        return $this->transportConfigModels;
    }

    /**
     * @return array<string>
     */
    public function getTransportDsnConvertors(): array
    {
        return $this->transportDsnConvertors;
    }

    /**
     * @return string
     */
    private function getString(array $services)
    {
        return '"'.implode('","', $services).'"';
    }

    public function addTransport(string $serviceId, string $translatableAlias, bool $showHost, bool $showPort, bool $showUser, bool $showPassword, bool $showApiKey, string $options, string $dsnConvertor): void
    {
        $this->transportTypes[$serviceId] = $translatableAlias;

        if ($showHost) {
            $this->showHost[] = $serviceId;
        }

        if ($showPort) {
            $this->showPort[] = $serviceId;
        }

        if ($showUser) {
            $this->showUser[] = $serviceId;
        }

        if ($showPassword) {
            $this->showPassword[] = $serviceId;
        }

        if ($showApiKey) {
            $this->showApiKey[] = $serviceId;
        }

        if ($options) {
            $this->transportConfigModels[$serviceId] = $options;
        }
        if ($dsnConvertor) {
            $this->transportDsnConvertors[$serviceId] = $dsnConvertor;
        }
    }
}
