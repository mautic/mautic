<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\CoreBundle\Helper\Dsn\Dsn as MauticDsn;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport as SymfonyTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;

class TransportFactory
{
    public function __construct(
        private readonly SymfonyTransportFactory $transportFactory,
    ) {
    }

    /**
     * @param array<string, string> $dsns
     */
    public function fromStrings(array $dsns): Transports
    {
        $transports = [];

        foreach ($dsns as $name => $dsn) {
            $transports[$name] = $this->fromString($dsn);
        }

        return new Transports($transports);
    }

    public function fromString(string $dsn): TransportInterface
    {
        try {
            return $this->fromStringWithoutUnsupportedSchemeFallback($dsn);
        } catch (UnsupportedSchemeException) {
            return new InvalidTransport();
        }
    }

    public function fromDsnObject(Dsn $dsn): TransportInterface
    {
        return $this->transportFactory->fromDsnObject($dsn);
    }

    public function fromStringForValidation(string $dsn): TransportInterface
    {
        return $this->fromStringWithoutUnsupportedSchemeFallback($dsn);
    }

    private function fromStringWithoutUnsupportedSchemeFallback(string $dsn): TransportInterface
    {
        try {
            return $this->transportFactory->fromString($dsn);
        } catch (IncompleteDsnException $exception) {
            $parsed = MauticDsn::fromString($dsn);

            if (null !== $parsed->getUser()
                || null === $parsed->getPassword()
                || preg_match('/(?:^|\+)smtps?$/', $parsed->getScheme())
            ) {
                throw $exception;
            }

            try {
                return $this->transportFactory->fromString((string) $parsed->setUser($parsed->getPassword())->setPassword(null));
            } catch (IncompleteDsnException) {
                throw $exception;
            }
        }
    }
}
