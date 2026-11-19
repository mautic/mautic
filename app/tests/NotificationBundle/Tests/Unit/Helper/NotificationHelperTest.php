<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\NotificationBundle\Helper\NotificationHelper;
use Mautic\NotificationBundle\Helper\OneSignalIntegrationHelper;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

final class NotificationHelperTest extends TestCase
{
    private const string SITE_URL = 'https://mautic.example';

    public function testTrackedPageScriptUsesDecryptedKeysAndIntegrationFeatureSettings(): void
    {
        $helper = $this->createHelper(
            $this->createIntegration(['tracking_page_enabled'], ['subdomain_name' => 'acme']),
            'http://customer.example/page'
        );

        $this->assertNotNull($helper->getHeaderScript());

        $script = $helper->getScript();
        $this->assertIsString($script);
        $this->assertStringContainsString('appId: "app-id"', $script);
        $this->assertStringContainsString('safari_web_id: "safari-id"', $script);
        $this->assertStringContainsString('subdomainName: "acme"', $script);
        $this->assertStringContainsString('welcomeNotification: { "disable": true },', $script);
        $this->assertStringContainsString("MauticJS.makeCORSRequest('GET', 'https://mautic.example/notification/subscribe', data);", $script);
    }

    public function testHttpsPageScriptSkipsSubdomainAndKeepsWelcomeNotification(): void
    {
        $helper = $this->createHelper(
            $this->createIntegration(['tracking_page_enabled', 'welcome_notification_enabled'], ['subdomain_name' => 'acme']),
            'https://customer.example/page'
        );

        $script = $helper->getScript();
        $this->assertIsString($script);
        $this->assertStringNotContainsString('subdomainName', $script);
        $this->assertStringNotContainsString('welcomeNotification', $script);
    }

    public function testNoScriptWithoutPublishedIntegration(): void
    {
        $helper = $this->createHelper(null, 'http://customer.example/page');

        $this->assertNull($helper->getHeaderScript());
        $this->assertNull($helper->getScript());
    }

    public function testNoScriptOnLandingPageUnlessLandingPagesAreEnabled(): void
    {
        $landingPage = self::SITE_URL.'/landing-page';

        $this->assertNull($this->createHelper($this->createIntegration(['tracking_page_enabled'], []), $landingPage)->getScript());
        $this->assertIsString($this->createHelper($this->createIntegration(['landing_page_enabled'], []), $landingPage)->getScript());
    }

    /**
     * @param string[]             $supportedFeatures
     * @param array<string, mixed> $featureSettings
     */
    private function createIntegration(array $supportedFeatures, array $featureSettings): OneSignalIntegration
    {
        $settings = new Integration();
        $settings->setIsPublished(true);
        $settings->setSupportedFeatures($supportedFeatures);
        $settings->setFeatureSettings(['integration' => $featureSettings]);
        $settings->setApiKeys(['app_id' => 'app-id', 'safari_web_id' => 'safari-id']);

        $integration = new OneSignalIntegration();
        $integration->setIntegrationSettings($settings);

        return $integration;
    }

    private function createHelper(?OneSignalIntegration $publishedIntegration, string $referer): NotificationHelper
    {
        $coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturn(self::SITE_URL);

        $oneSignalIntegrationHelper = $this->createStub(OneSignalIntegrationHelper::class);
        $oneSignalIntegrationHelper->method('getPublishedIntegration')->willReturn($publishedIntegration);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn(self::SITE_URL.'/notification/subscribe');

        $requestStack = new RequestStack([new Request(server: ['HTTP_REFERER' => $referer])]);

        return new NotificationHelper($coreParametersHelper, $oneSignalIntegrationHelper, $router, $requestStack);
    }
}
