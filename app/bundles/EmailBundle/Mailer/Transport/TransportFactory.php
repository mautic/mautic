<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\CoreBundle\Helper\Dsn\Dsn as MauticDsn;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport as SymfonyTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;
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
            if ($this->isCompoundDsn($dsn)) {
                try {
                    return $this->fromCompoundDsn($dsn);
                } catch (IncompleteDsnException) {
                    throw $exception;
                }
            }

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

    private function isCompoundDsn(string $dsn): bool
    {
        return str_starts_with($dsn, 'failover(') || str_starts_with($dsn, 'roundrobin(');
    }

    private function fromCompoundDsn(string $dsn): TransportInterface
    {
        [$scheme, $arguments, $retryPeriod] = $this->parseCompoundDsn($dsn);
        $transports = [];

        foreach ($arguments as $argument) {
            $transports[] = $this->fromStringWithoutUnsupportedSchemeFallback($argument);
        }

        return 'failover' === $scheme
            ? new FailoverTransport($transports, $retryPeriod)
            : new RoundRobinTransport($transports, $retryPeriod);
    }

    /**
     * @return array{string, list<string>, int}
     */
    private function parseCompoundDsn(string $dsn): array
    {
        $scheme       = str_starts_with($dsn, 'failover(') ? 'failover' : 'roundrobin';
        $openingIndex = strlen($scheme);
        $closingIndex = $this->findCompoundClosingParenthesis($dsn, $openingIndex);
        $arguments = $this->splitCompoundArguments(substr($dsn, $openingIndex + 1, $closingIndex - $openingIndex - 1));
        $suffix    = substr($dsn, $closingIndex + 1);

        if ('' !== $suffix && !preg_match('/^\?retry_period=(\d+)$/', $suffix, $matches)) {
            throw new InvalidArgumentException('The mailer DSN has some garbage at the end.');
        }

        return [$scheme, $arguments, isset($matches[1]) && 0 !== (int) $matches[1] ? (int) $matches[1] : 60];
    }

    private function findCompoundClosingParenthesis(string $dsn, int $openingIndex): int
    {
        $depth = 0;

        for ($index = $openingIndex; $index < strlen($dsn); ++$index) {
            if ('(' === $dsn[$index]) {
                ++$depth;
            } elseif (')' === $dsn[$index] && 0 === --$depth) {
                return $index;
            }
        }

        throw new IncompleteDsnException('The compound mailer DSN is incomplete.');
    }

    /**
     * @return list<string>
     */
    private function splitCompoundArguments(string $arguments): array
    {
        $result = [];
        $depth = 0;
        $start = 0;
        $length = strlen($arguments);

        for ($index = 0; $index < $length; ++$index) {
            if ('(' === $arguments[$index]) {
                ++$depth;
            } elseif (')' === $arguments[$index]) {
                --$depth;
            } elseif (ctype_space($arguments[$index]) && 0 === $depth) {
                if ($index > $start) {
                    $result[] = substr($arguments, $start, $index - $start);
                }

                while ($index + 1 < $length && ctype_space($arguments[$index + 1])) {
                    ++$index;
                }

                $start = $index + 1;
            }
        }

        if ($start < $length) {
            $result[] = substr($arguments, $start);
        }

        return $result;
    }
}
