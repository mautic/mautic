<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Mautic\CoreBundle\Helper\Dsn\DsnGenerator;

class DsnGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataDsnProvider
     */
    public function testGetDsnString(Dsn $dsn, string $dsnString): void
    {
        $this->assertEquals($dsnString, DsnGenerator::getDsnString($dsn));
    }

    /**
     * data to test DSN conversion.
     *
     * @return array<string, array<Dsn|string>>
     */
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
                new Dsn('smtp', 'host', 'user', 'password', 25, ['encryption' => 'tls', 'auth_mode'=>'login']), 'smtp://user:password@host:25?encryption=tls&auth_mode=login',
            ],
        ];
    }
}
