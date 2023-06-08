<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\TransportNotFoundException;
use Mautic\EmailBundle\Mailer\Exception\UnsupportedTransportException;

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

    /**
     * @throws TransportNotFoundException
     * @throws UnsupportedTransportException
     */
    public function getCallbackSupportExtension(string $transportName): CallbackTransportInterface
    {
        $transportExtension = $this->getTransportExtension($transportName);

        if (!$transportExtension instanceof CallbackTransportInterface) {
            throw UnsupportedTransportException::fromName($transportName, 'Request callback');
        }

        return $transportExtension;
    }

    /**
     * @throws TransportNotFoundException
     * @throws UnsupportedTransportException
     */
    public function getTestConnectionExtension(string $transportName): TestConnectionInterface
    {
        $transportExtension = $this->getTransportExtension($transportName);

        if (!$transportExtension instanceof TestConnectionInterface) {
            throw UnsupportedTransportException::fromName($transportName, 'Connection test');
        }

        return $transportExtension;
    }
}
