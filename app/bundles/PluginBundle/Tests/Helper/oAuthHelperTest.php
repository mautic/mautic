<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Helper;

use Mautic\PluginBundle\Helper\oAuthHelper;
use PHPUnit\Framework\TestCase;

final class oAuthHelperTest extends TestCase
{
    /**
     * @param array<int, string> $headers
     *
     * @dataProvider dataForHashSensitiveHeaderData
     */
    public function testHashSensitiveHeaderData(string $authorization, array $headers): void
    {
        $hashedHeaders = oAuthHelper::sanitizeHeaderData($headers);

        $this->assertStringContainsString(sprintf('Authorization: %s [REDACTED]', $authorization), $hashedHeaders[0]);
    }

    /**
     * @return \Generator<string, array<int, string|array<int, string>>>
     */
    public function dataForHashSensitiveHeaderData(): \Generator
    {
        yield 'For Bearer' => [
            'Bearer',
            [
                'Authorization: Bearer SME-ASA',
            ],
        ];

        yield 'For Basic' => [
            'Basic',
            [
                'Authorization: Basic YWxhZGRpbjpvcGVuc2VzYW1l',
            ],
        ];
    }
}
