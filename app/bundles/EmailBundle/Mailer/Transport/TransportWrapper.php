<?php

namespace Mautic\EmailBundle\Mailer\Transport;

class TransportWrapper
{
    /** @var array <string, TransportExtensionInterface> */
    private array $transportExtensions = [];

    public function addTransportExtension(TransportExtensionInterface $transportExtension): void
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

    /**
     * @throws \LogicException
     */
    public function getTransportExtension(string $transportName): TransportExtensionInterface
    {
        if (!array_key_exists($transportName, $this->transportExtensions)) {
            throw new \LogicException('Transport Extension '.$transportName.' is not found');
        }

        return $this->transportExtensions[$transportName];
    }
}
