<?php

namespace Mautic\EmailBundle\Tests\Mailer\Dsn;

use Mautic\EmailBundle\Mailer\Dsn\Dsn;
use Mautic\EmailBundle\Mailer\Dsn\DsnGenerator;

class DsnGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataDsnProvider
     */
    public function testGetDsnString(Dsn $dsn, string $dsnString): void
    {
        $this->assertEquals($dsnString, DsnGenerator::getDsnString($dsn));
    }

    public function dataDsnProvider(): array
    {
        return [
            'smtp with host' => [
                new Dsn('smtp', 'host'), 'smtp://host',
            ],
            'smtp with host and user' => [
                new Dsn('smtp', 'host', 'user'), 'smtp://user@host',
            ],
            'smtp with host, user, password' => [
                new Dsn('smtp', 'host', 'user', 'password'), 'smtp://user:password@host',
            ],
            'smtp with host, port, user, password' => [
                new Dsn('smtp', 'host', 'user', 'password', 25), 'smtp://user:password@host:25',
            ],
            'redis with host port and path' => [
                new Dsn('redis', 'host', null, null, 63197, ['path' => 'emails']), 'redis://host:63197/emails',
            ],
            'redis with host port and path and option' => [
                new Dsn('redis', 'host', null, null, 63197, ['path' => 'emails', 'auto_setup' => 'true']), 'redis://host:63197/emails?auto_setup=true',
            ],
        ];
    }
}
