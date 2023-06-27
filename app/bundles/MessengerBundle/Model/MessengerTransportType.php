<?php

namespace Mautic\MessengerBundle\Model;

class MessengerTransportType
{
    public const TRANSPORT_ALIAS = 'transport_alias';

    public const FIELD_HOST = 'field_host';

    public const FIELD_PORT = 'field_port';

    public const FIELD_USER = 'field_user';

    public const FIELD_PASSWORD = 'field_password';

    public const TRANSPORT_OPTIONS = 'transport_options';

    public const DSN_CONVERTOR = 'transport_dsn';

    /**
     * @var string[]
     */
    private $transportTypes = [
        'doctrine' => 'mautic.messenger.config.transport.doctrine',
    ];

    /**
     * @var array<string>
     */
    private $showHost = [];

    /**
     * @var array<string>
     */
    private $showPort = [];

    /**
     * @var array<string>
     */
    private $showUser = [];

    /**
     * @var array<string>
     */
    private $showPassword = [];

    /**
     * @var array<string>
     */
    private $transportConfigModels = [];

    /**
     * @var array<string>
     */
    private $transportDsnConvertors = [];

    /**
     * Add New Transprot to the Messenger bundle configuration.
     */
    public function addTransport(string $serviceId, string $translatableAlias, bool $showHost, bool $showPort, bool $showUser, bool $showPassword, string $options, string $dsnConvertor): void
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

        $this->transportConfigModels[$serviceId] = $options;

        $this->transportDsnConvertors[$serviceId] = $dsnConvertor;
    }

    /**
     * @return array<string>
     */
    public function getTransportTypes(): array
    {
        return $this->transportTypes;
    }

    /**
     * @return array<string>
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
    public function getServiceRequiresPassword()
    {
        return $this->getString($this->showPassword);
    }

    /**
     * @param array<string> $services
     */
    private function getString(array $services): string
    {
        return '"'.implode('","', $services).'"';
    }
}
