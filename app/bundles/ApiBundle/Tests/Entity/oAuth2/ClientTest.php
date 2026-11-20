<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Entity\oAuth2;

use Mautic\ApiBundle\Entity\oAuth2\Client;
use OAuth2\OAuth2;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testAllowedGrantTypesOnConstruction(): void
    {
        $client = new Client();

        $allowedGrantTypes = $client->getAllowedGrantTypes();

        $this->assertCount(2, $allowedGrantTypes);
        $this->assertSame([
            OAuth2::GRANT_TYPE_AUTH_CODE,
            OAuth2::GRANT_TYPE_REFRESH_TOKEN,
        ], $allowedGrantTypes);
    }

    public function testTokensAreGeneratedFromAllRandomBytes(): void
    {
        $client      = new Client();
        $otherClient = new Client();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $client->getRandomId());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $client->getSecret());
        $this->assertNotSame($client->getRandomId(), $client->getSecret());
        $this->assertNotSame($client->getRandomId(), $otherClient->getRandomId());
    }
}
