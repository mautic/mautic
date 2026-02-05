<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\MarketplaceBundle\Service\Config;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class RateControllerTest extends MauticMysqlTestCase
{
    public function testRatePageDisplaysLoginPrompt(): void
    {
        $this->client->request('GET', 's/marketplace/rate/package/koco/mautic-recaptcha-bundle');

        Assert::assertSame(SymfonyResponse::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $responseContent = $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('auth0-login-btn', $responseContent);
        Assert::assertStringContainsString('koco/mautic-recaptcha-bundle', $responseContent);
    }

    public function testRatePageContainsAuth0Script(): void
    {
        $this->client->request('GET', 's/marketplace/rate/package/koco/mautic-recaptcha-bundle');

        $config = static::getContainer()->get('marketplace.service.config');
        \assert($config instanceof Config);

        $responseContent = $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('auth0-spa-js', $responseContent);
        Assert::assertStringContainsString($config->getAuth0Domain(), $responseContent);
    }

    public function testRatePageContainsReviewForm(): void
    {
        $this->client->request('GET', 's/marketplace/rate/package/koco/mautic-recaptcha-bundle');

        $responseContent = $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('review-form', $responseContent);
        Assert::assertStringContainsString('rating-stars', $responseContent);
    }
}
