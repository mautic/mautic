<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * This test is breaking other tests, so running it in a separate process.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class JsControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['google_analytics_id']                   = 'G-F3825DS9CD';
        $this->configParams['google_analytics_trackingpage_enabled'] = true;
        $this->configParams['google_analytics_anonymize_ip']         = 'testIndexActionRendersSuccessfullyWithAnonymizeIp' === $this->name();
        $this->configParams['facebook_pixel_id']                     = 'FB-TEST';
        $this->configParams['facebook_pixel_trackingpage_enabled']   = true;
        parent::setUp();
    }

    public function testIndexActionRendersSuccessfully(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', $content);
        $this->assertStringContainsString('window.gtag(\'config\',\'G-F3825DS9CD\')', $content);
        $runtimeReadyPosition    = strpos($content, 'runtimeReady');
        $trackingEnabledPosition = strrpos($content, 'trackingEnabled');
        $this->assertNotFalse($runtimeReadyPosition);
        $this->assertNotFalse($trackingEnabledPosition);
        $this->assertLessThan($trackingEnabledPosition, $runtimeReadyPosition);
    }

    public function testIndexActionRendersSuccessfullyWithAnonymizeIp(): void
    {
        $this->client->request('GET', '/mtc.js');
        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('window.gtag(\'config\',\'G-F3825DS9CD\',{"anonymize_ip":!0})', (string) $this->client->getResponse()->getContent());
    }

    public function testEssentialEndpointContainsAnonymousRuntimeOnly(): void
    {
        $this->client->request('GET', '/mautic-essential.js');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('runtimeReady', $content);
        $this->assertStringContainsString('appendTrackedContact', $content);
        $this->assertStringContainsString('requestWithCredentials', $content);
        $this->assertStringContainsString('replaceDynamicContent', $content);
        $this->assertStringContainsString('enhanceDynamicContent', $content);
        $this->assertStringNotContainsString('mtc_id', $content);
        $this->assertStringNotContainsString('mautic_device_id', $content);
        $this->assertStringNotContainsString('getTrackedContact', $content);
        $this->assertStringNotContainsString('setTrackedContact(response)', $content);
        $this->assertStringNotContainsString('deliverPageEvent', $content);
        $this->assertStringNotContainsString('/mtc/event', $content);
        $this->assertStringNotContainsString('/mtracking.gif', $content);
        $this->assertStringNotContainsString('setTrackedEvents', $content);
        $this->assertStringNotContainsString('focus_item', $content);
        $this->assertStringNotContainsString('https://connect.facebook.net/en_US/fbevents.js', $content);
    }

    public function testTrackingEndpointContainsIdentityWithoutRuntime(): void
    {
        $this->client->request('GET', '/mautic-tracking.js');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('runtimeReady', $content);
        $this->assertStringContainsString("localStorage.getItem('mtc_id')", $content);
        $this->assertStringContainsString('onDynamicContentResponse', $content);
        $this->assertStringContainsString('deliverPageEvent', $content);
        $this->assertStringContainsString('/mtc/event', $content);
        $this->assertStringContainsString('/mtracking.gif', $content);
        $this->assertStringContainsString('setTrackedEvents', $content);
        $this->assertStringContainsString('mautic:tracking-enabled', $content);
        $this->assertStringContainsString('focus_item', $content);
        $this->assertGreaterThan(strpos($content, 'focus_item'), strpos($content, 'mautic:tracking-enabled'));
        $this->assertStringContainsString('https://connect.facebook.net/en_US/fbevents.js', $content);
        $this->assertStringNotContainsString('serialize=function', $content);
        $this->assertStringNotContainsString('setCookie=function', $content);
        $this->assertStringNotContainsString('replaceDynamicContent=function', $content);
    }

    #[DataProvider('scriptEndpointProvider')]
    public function testScriptEndpoints(string $path, bool $containsTracking): void
    {
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/javascript');

        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('@package     MauticJS', $content);

        $trackingMarker = 'https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD';
        if ($containsTracking) {
            $this->assertStringContainsString($trackingMarker, $content);
        } else {
            $this->assertStringNotContainsString($trackingMarker, $content);
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
