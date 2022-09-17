<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Symfony\Component\Mailer\Transport\TransportInterface;

class TransportWrapper
{
    private TransportInterface $transport;
    private array $transportExtensions = [];

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function addTransportExtension(TransportExtensionInterface $transportExtension)
    {
        foreach ($transportExtension->getSupportedSchemes() as $scheme) {
            $this->transportExtensions[$scheme] = $transportExtension;
        }
    }

    public function isSupportCallback(string $transportName): bool
    {
        if (!array_key_exists($transportName, $this->transportExtensions)) {
            return false;
        }

        return $this->transportExtensions[$transportName] instanceof CallbackTransportInterface;
    }

    public function getTransportExtension(string $transportName): TransportExtensionInterface
    {
        if (!array_key_exists($transportName, $this->transportExtensions)) {
            return new \LogicException('Transport Extension '.$transportName.' is not found');
        }

        return $this->transportExtensions[$transportName];
    }
}
