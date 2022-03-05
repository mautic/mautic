<?php

namespace Mautic\EmailBundle\Model;

class MessengerType
{
    /**
     * @var string[]
     */
    private $messengerTypes = [
        'redis' => 'redis',
    ];

    /**
     * @var string[]
     */
    private $showHost = [
        'redis',
    ];

    private array $showPort = [
        'redis',
    ];

    private array $showStream = [
        'redis',
    ];

    private array $showGroup = [
        'redis',
    ];

    private array $showAutoSetup = [
        'redis',
    ];

    private array $showTls = [
        'redis',
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
