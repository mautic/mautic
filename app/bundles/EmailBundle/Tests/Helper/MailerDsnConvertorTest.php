<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\MailerDsnConvertor;

class MailerDsnConvertorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataConvertArrayToDsnStringProvider
     *
     * @param array<string> $parameters
     */
    public function testConvertArrayToDsnString(array $parameters, string $dsn): void
    {
        $convertedDsn = MailerDsnConvertor::convertArrayToDsnString($parameters);
        $this->assertEquals($dsn, $convertedDsn);
    }

    /**
     * data to test DSN conversion.
     *
     * @return array<string, array<int, array<string, string|null>|string>>
     */
    public function dataConvertArrayToDsnStringProvider(): array
    {
        return [
            'smtp://null' => [
                [
                    'mailer_transport' => 'smtp',
                    'mailer_host'      => 'null',
                    'mailer_user'      => null,
                    'mailer_password'  => null,
                    'mailer_port'      => null,
                ],
                'smtp://null',
            ],
        ];
    }
}
