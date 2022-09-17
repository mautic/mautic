<?php

namespace Mautic\EmailBundle\Tests\Mailer\Dsn;

use Mautic\EmailBundle\Mailer\Dsn\DsnGenerator;
use Symfony\Component\Mailer\Transport\Dsn;

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
     * @return array<string, array<int, string|\Symfony\Component\Mailer\Transport\Dsn>>
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
        ];
    }
}
