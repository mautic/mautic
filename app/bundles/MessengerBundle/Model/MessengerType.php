<?php

namespace Mautic\MessengerBundle\Model;

class MessengerType
{
    /**
     * @var string[]
     */
    private $messengerTypes = [
        'doctrine' => 'mautic.messenger.config.transport.doctrine',
    ];

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
    public function getMessengerTypes(): array
    {
        return $this->messengerTypes;
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
