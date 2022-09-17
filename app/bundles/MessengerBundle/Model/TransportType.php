<?php

namespace Mautic\MessengerBundle\Model;

class TransportType
{
    public const TRANSPORT_ALIAS = 'transport_alias';

    public const TRANSPORT_OPTIONS = 'transport_options';

    /**
     * @var string[]
     */
    private $transportTypes = [
        'mautic.messenger.doctrine' => 'mautic.messenger.config.transport.doctrine',
    ];

    /**
     * @param $serviceId
     * @param $translatableAlias
     */
    public function addTransport($serviceId, $translatableAlias)
    {
        $this->transportTypes[$serviceId] = $translatableAlias;
    }

    /**
     * @var string[]
     */
    private $showHost = [
    ];

    private array $showPort = [
    ];

    private array $showStream = [
    ];

    private array $showGroup = [
    ];

    private array $showAutoSetup = [
    ];

    private array $showTls = [
    ];

    /**
     * @return string[]
     */
    public function getTrasportTypes(): array
    {
        return $this->transportTypes;
    }

    public function getServiceRequiresHost(): string
    {
        return $this->getString($this->showHost);
    }

    public function getServiceRequiresPort(): string
    {
        return $this->getString($this->showPort);
    }

    public function getServiceRequiresStream(): string
    {
        return $this->getString($this->showStream);
    }

    public function getServiceRequiresGroup(): string
    {
        return $this->getString($this->showGroup);
    }

    public function getServiceRequiresAutoSetup(): string
    {
        return $this->getString($this->showAutoSetup);
    }

    public function getServiceRequiresTls(): string
    {
        return $this->getString($this->showTls);
    }

    /**
     * @return string
     */
    private function getString(array $services)
    {
        return '"'.implode('","', $services).'"';
    }
}
