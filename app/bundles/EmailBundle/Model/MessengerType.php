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

    /**
     * @var string[]
     */
    private $showPort = [
        'redis',
    ];

    /**
     * @var string[]
     */
    private $showPath = [
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

    public function getServiceRequiresPath(): string
    {
        return $this->getString($this->showPath);
    }

    /**
     * @return string
     */
    private function getString(array $services)
    {
        return '"'.implode('","', $services).'"';
    }
}
