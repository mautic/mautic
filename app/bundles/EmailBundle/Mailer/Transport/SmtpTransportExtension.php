<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;

class SmtpTransportExtension implements TransportExtensionInterface, TestConnectionInterface
{
    public function getSupportedSchemes(): array
    {
        return ['smtp'];
    }

    /** {@inheritdoc} */
    public function testConnection(array $settings): bool
    {
        try {
            $connect = fsockopen($settings['mailer_host'], $settings['mailer_port'], $errno, $errstr, 10);
        } catch (\Throwable $exception) {
            throw new ConnectionErrorException($exception->getMessage());
        }
        $response = fgets($connect, 4096);
        if (empty($connect)) {
            throw new ConnectionErrorException($response);
        }

        fclose($connect);

        /*
         * TODO: Add more checks for SMTP connection
         */

        return true;
    }
}
