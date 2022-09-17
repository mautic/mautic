<?php

namespace Mautic\EmailBundle\Tests\Mailer\Dsn;

use Mautic\EmailBundle\Mailer\Dsn\MailerDsnConvertor;

class MailerDsnConvertorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataConvertArrayToDsnStringProvider
     */
    public function testConvertArrayToDsnString(array $parameters, string $dsn): void
    {
        $convertedDsn = MailerDsnConvertor::convertArrayToDsnString($parameters);
        $this->assertEquals($dsn, $convertedDsn);
    }

    public function dataConvertArrayToDsnStringProvider(): array
    {
        return [
            'null://null' => [
                [
                    'mailer_transport' => 'null',
                    'mailer_host'      => 'null',
                    'mailer_user'      => null,
                    'mailer_password'  => null,
                    'mailer_port'      => null,
                ],
                'null://null',
            ],
        ];
    }
}
