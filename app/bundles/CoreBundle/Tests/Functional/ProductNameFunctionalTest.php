<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProductNameFunctionalTest extends MauticMysqlTestCase
{
    public function testTheProductNameIsATranslationThatReachesTitlesAndScripts(): void
    {
        $this->assertSame('Mautic', static::getContainer()->get('translator')->trans('mautic.core.product_name'));

        $crawler = $this->client->request(Request::METHOD_GET, '/s/dashboard');

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('Mautic', $crawler->filter('title')->text());
        $this->assertMatchesRegularExpression(
            '/var mauticProductName\s*=\s*"Mautic";/',
            (string) $this->client->getResponse()->getContent()
        );
    }
}
