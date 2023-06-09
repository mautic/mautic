<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;

class SmtpTransportExtension implements TransportExtensionInterface, TestConnectionInterface
{
    public function getSupportedSchemes(): array
    {
        return ['smtp'];
    }

    public function testConnection(array $settings): bool
    {
        if (empty($settings['mailer_host'])) {
            throw new ConnectionErrorException('Host is empty.');
        }

        if (empty($settings['mailer_port'])) {
            throw new ConnectionErrorException('Port is empty.');
        }

        try {
            $connect = fsockopen($settings['mailer_host'], (int) $settings['mailer_port'], timeout: 10);
        } catch (\Throwable) {
            throw new ConnectionErrorException();
        }

        if (false === $connect) {
            throw new ConnectionErrorException();
        }

        try {
            $response = fgets($connect, 4096);

            if (empty($response)) {
                throw new ConnectionErrorException();
            }
        } finally {
            fclose($connect);
        }

        return true;
    }
}
