<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

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
            return $this->transportFactory->fromString($this->normalizeSendgridDsn($dsn));
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
        return $this->transportFactory->fromString($this->normalizeSendgridDsn($dsn));
    }

    private function normalizeSendgridDsn(string $dsn): string
    {
        return (string) preg_replace_callback(
            '/^([^:]+):\\/\\/:(.*?)@/i',
            static function (array $matches): string {
                if (!in_array(strtolower($matches[1]), self::KEY_ONLY_API_SCHEMES, true)) {
                    return $matches[0];
                }

                return $matches[1].'://'.$matches[2].'@';
            },
            $dsn,
            1
        );
    }

}
