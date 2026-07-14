<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * This test is breaking other tests, so running it in a separate process.
 */
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class JsControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['google_analytics_id']                   = 'G-F3825DS9CD';
        $this->configParams['google_analytics_trackingpage_enabled'] = true;
        $this->configParams['google_analytics_anonymize_ip']         = 'testIndexActionRendersSuccessfullyWithAnonymizeIp' === $this->name();
        parent::setUp();
    }

    public function testIndexActionRendersSuccessfully(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', (string) $this->client->getResponse()->getContent());
        Assert::assertStringContainsString('gtag(\'config\',\'G-F3825DS9CD\')', (string) $this->client->getResponse()->getContent());
    }

    public function testIndexActionRendersSuccessfullyWithAnonymizeIp(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', (string) $this->client->getResponse()->getContent());
        Assert::assertStringContainsString('gtag(\'config\',\'G-F3825DS9CD\',{"anonymize_ip":!0})', (string) $this->client->getResponse()->getContent());
    }

    #[DataProvider('scriptEndpointProvider')]
    public function testScriptEndpoints(string $path, bool $containsTracking): void
    {
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/javascript');

        $content = (string) $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('@package     MauticJS', $content);

        $trackingMarker = 'https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD';
        if ($containsTracking) {
            Assert::assertStringContainsString($trackingMarker, $content);
        } else {
            Assert::assertStringNotContainsString($trackingMarker, $content);
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function scriptEndpointProvider(): iterable
    {
        yield 'legacy aggregate' => ['/mtc.js', true];
        yield 'essential' => ['/mautic-essential.js', false];
        yield 'tracking' => ['/mautic-tracking.js', true];
    }
}
