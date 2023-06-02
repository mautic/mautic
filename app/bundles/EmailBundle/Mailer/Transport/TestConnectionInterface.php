<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;

interface TestConnectionInterface
{
    /**
     * Test Email settings and connection.
     *
     * @param array<string, mixed> $settings Email settings
     *
     * @return bool True if connection is successful
     *
     * @throws ConnectionErrorException
     */
    public function testConnection(array $settings): bool;
}
