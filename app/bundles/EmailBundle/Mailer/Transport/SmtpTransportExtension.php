<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;
use Symfony\Component\Mailer\Transport\Dsn;

class SmtpTransportExtension implements TransportExtensionInterface, TestConnectionInterface
{
    public function getSupportedSchemes(): array
    {
        return ['smtp'];
    }

    /** {@inheritdoc} */
    public function testConnection(Dsn $dsn): bool
    {
        try {
            $connect = fsockopen($dsn->getHost(), $dsn->getPort(), $errno, $errstr, 10);
        } catch (\Throwable $exception) {
            throw new ConnectionErrorException($exception->getMessage());
        }
        $response = fgets($connect, 4096);
        if (empty($connect)) {
            throw new ConnectionErrorException($response);
        }

        return true;
    }
}
