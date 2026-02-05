<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\MarketplaceBundle\Service\Config;
use PHPUnit\Framework\Assert;

final class RateControllerTest extends MauticMysqlTestCase
{
    public function testRatePageDisplaysLoginPrompt(): void
    {
        $this->client->request('GET', 's/marketplace/rate/package/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('auth0-login-btn', $responseContent);
        Assert::assertStringContainsString('koco/mautic-recaptcha-bundle', $responseContent);
    }

    public function testRatePageContainsAuth0Config(): void
    {
        $this->client->request('GET', 's/marketplace/rate/package/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $config = static::getContainer()->get('marketplace.service.config');
        \assert($config instanceof Config);

        $responseContent = $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('MarketplaceRating.init()', $responseContent);
        Assert::assertStringContainsString($config->getAuth0Domain(), $responseContent);
    }

    public function testRatePageContainsReviewForm(): void
    {
        $this->client->request('GET', 's/marketplace/rate/package/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('review-form', $responseContent);
        Assert::assertStringContainsString('rating-stars', $responseContent);
    }
}
