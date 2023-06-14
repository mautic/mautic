<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\TransportNotFoundException;

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

    /**
     * @throws TransportNotFoundException
     */
    public function getTransportExtension(string $transportName): TransportExtensionInterface
    {
        if (!isset($this->transportExtensions[$transportName])) {
            throw TransportNotFoundException::fromName($transportName);
        }

        return $this->transportExtensions[$transportName];
    }
}
