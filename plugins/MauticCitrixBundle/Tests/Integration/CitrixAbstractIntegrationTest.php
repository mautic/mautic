<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        $this->citrixTestIntegration       = new class() extends CitrixAbstractIntegration {
            public array $keys             = [];

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
