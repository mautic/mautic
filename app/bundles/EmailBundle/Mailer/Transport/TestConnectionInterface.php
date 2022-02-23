<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;
use Symfony\Component\Mailer\Transport\Dsn;

interface TestConnectionInterface
{
    /**
     * @throw ConnectionErrorException
     */
    public function testConnection(Dsn $dsn): bool;
}
