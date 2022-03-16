<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCitrixBundle\Tests\Integration;

use MauticPlugin\MauticCitrixBundle\Integration\CitrixAbstractIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class CitrixAbstractIntegrationTest extends TestCase
{
    /**
     * @var CitrixAbstractIntegration
     */
    private $citrixTestIntegration;

    protected function setUp(): void
    {
        $this->citrixTestIntegration = new class() extends CitrixAbstractIntegration {
            /**
             * @var array<string,string>
             */
            public array $keys = [];

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
            }

            public function getName(): string
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider dataGetBearerToken
     */
    public function testGetBearerToken(bool $inAuthorization, array $keys, ?string $expectedBearerToken): void
    {
        $this->citrixTestIntegration->keys = $keys;

        Assert::assertSame($expectedBearerToken, $this->citrixTestIntegration->getBearerToken($inAuthorization));
    }

    public function dataGetBearerToken(): iterable
    {
        yield [false, [], null];
        yield [true, [], null];
        yield [false, ['access_token' => 'some-token'], 'some-token'];
        yield [true, ['access_token' => 'some-token'], null];
    }
}
