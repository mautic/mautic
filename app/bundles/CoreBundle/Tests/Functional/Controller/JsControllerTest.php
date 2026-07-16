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
        $content = (string) $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', $content);
        Assert::assertStringContainsString('gtag(\'config\',\'G-F3825DS9CD\')', $content);
        $runtimeReadyPosition    = strpos($content, 'runtimeReady');
        $trackingEnabledPosition = strrpos($content, 'trackingEnabled');
        Assert::assertNotFalse($runtimeReadyPosition);
        Assert::assertNotFalse($trackingEnabledPosition);
        Assert::assertLessThan($trackingEnabledPosition, $runtimeReadyPosition);
    }

    public function testIndexActionRendersSuccessfullyWithAnonymizeIp(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', (string) $this->client->getResponse()->getContent());
        Assert::assertStringContainsString('gtag(\'config\',\'G-F3825DS9CD\',{"anonymize_ip":!0})', (string) $this->client->getResponse()->getContent());
    }

    public function testEssentialEndpointContainsAnonymousRuntimeOnly(): void
    {
        $this->client->request('GET', '/mautic-essential.js');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('runtimeReady', $content);
        Assert::assertStringContainsString('appendTrackedContact', $content);
        Assert::assertStringContainsString('requestWithCredentials', $content);
        Assert::assertStringContainsString('replaceDynamicContent', $content);
        Assert::assertStringContainsString('enhanceDynamicContent', $content);
        Assert::assertStringNotContainsString("localStorage.getItem('mtc_id')", $content);
        Assert::assertStringNotContainsString('getTrackedContact', $content);
        Assert::assertStringNotContainsString('setTrackedContact(response)', $content);
    }

    public function testTrackingEndpointContainsIdentityWithoutRuntime(): void
    {
        $this->client->request('GET', '/mautic-tracking.js');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        Assert::assertStringContainsString('runtimeReady', $content);
        Assert::assertStringContainsString("localStorage.getItem('mtc_id')", $content);
        Assert::assertStringContainsString('onDynamicContentResponse', $content);
        Assert::assertStringNotContainsString('serialize=function', $content);
        Assert::assertStringNotContainsString('setCookie=function', $content);
        Assert::assertStringNotContainsString('replaceDynamicContent=function', $content);
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
