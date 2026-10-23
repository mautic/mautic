<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport as SymfonyTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;

class TransportFactory
{
    /**
     * @var list<string>
     */
    private const KEY_ONLY_API_SCHEMES = [
        'brevo+api',
        'mailersend+api',
        'mandrill+api',
        'postmark+api',
        'resend+api',
        'sendgrid',
        'sendgrid+api',
    ];

    /**
     * @var list<string>
     */
    private const MULTI_CREDENTIAL_API_SCHEMES = [
        'mailgun+api',
        'mailjet+api',
        'scaleway+api',
        'ses+api',
    ];

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
        $normalizedDsn = $this->normalizePasswordOnlyApiDsn($dsn);

        try {
            return $this->transportFactory->fromString($normalizedDsn);
        } catch (IncompleteDsnException $exception) {
            if ($normalizedDsn === $dsn && $this->canRetryPasswordOnlyApiDsn($dsn, $exception)) {
                return $this->transportFactory->fromString($this->movePasswordToUser($dsn));
            }

            throw $exception;
        }
    }

    private function normalizePasswordOnlyApiDsn(string $dsn): string
    {
        return (string) preg_replace_callback(
            '/^([^:]+):\\/\\/:(.*?)@/i',
            static function (array $matches): string {
                if (!in_array(strtolower($matches[1]), self::KEY_ONLY_API_SCHEMES, true)) {
                    return $matches[0];
                }

                return $matches[1].'://'.str_replace(':', '%3A', $matches[2]).'@';
            },
            $dsn,
            1
        );
    }

    private function canRetryPasswordOnlyApiDsn(string $dsn, IncompleteDsnException $exception): bool
    {
        if (!str_contains($exception->getMessage(), 'User is not set')) {
            return false;
        }

        if (!preg_match('/^([^:]+):\/\/:([^@]+)@/i', $dsn, $matches)) {
            return false;
        }

        $scheme = strtolower($matches[1]);

        return str_ends_with($scheme, '+api') && !in_array($scheme, self::MULTI_CREDENTIAL_API_SCHEMES, true);
    }

    private function movePasswordToUser(string $dsn): string
    {
        return (string) preg_replace_callback(
            '/^([^:]+):\/\/:([^@]+)@/i',
            static fn (array $matches): string => $matches[1].'://'.str_replace(':', '%3A', $matches[2]).'@',
            $dsn,
            1
        );
    }
}
