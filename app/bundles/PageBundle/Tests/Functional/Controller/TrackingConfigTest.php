<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

final class TrackingConfigTest extends MauticMysqlTestCase
{
    public function testTrackingScriptOptionsAreRendered(): void
    {
        $this->saveSharedFocusConsentSetting(false);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=trackingconfig');

        self::assertResponseIsSuccessful();

        $getSnippet = static function (string $ariaLabel, ?string $focusConsent = null) use ($crawler): string {
            $selectorPrefix = '';
            if (null !== $focusConsent) {
                $selectorPrefix = sprintf('[data-focus-consent-snippet="%s"] ', $focusConsent);
            }

            $selector   = $selectorPrefix.sprintf('pre[aria-label="%s"]', $ariaLabel);
            $snippet    = $crawler->filter($selector);
            $copyButton = $crawler->filter($selector.' ~ [data-copy]');
            Assert::assertCount(1, $snippet);
            Assert::assertCount(1, $copyButton);
            $snippetText = $snippet->text(null, false);
            Assert::assertSame($snippetText, $copyButton->attr('data-copy'));
            Assert::assertStringNotContainsString('&lt;', $snippetText);

            return $snippetText;
        };

        $independentConsent = $crawler->filter('#config_trackingconfig_focus_uses_mautic_tracking_consent_0');
        $sharedConsent      = $crawler->filter('#config_trackingconfig_focus_uses_mautic_tracking_consent_1');
        $this->assertCount(1, $independentConsent);
        $this->assertCount(1, $sharedConsent);
        $this->assertCount(0, $crawler->filter('.code-snippet--multi script'));
        $this->assertNotNull($independentConsent->attr('checked'));
        $this->assertNull($sharedConsent->attr('checked'));
        $this->assertStringContainsString('Mautic.toggleFocusTrackingConsentSnippets(this)', (string) $sharedConsent->attr('onchange'));
        $this->assertCount(2, $crawler->filter('[data-focus-consent-snippet="independent"]:not(.hide)'));
        $this->assertCount(2, $crawler->filter('[data-focus-consent-snippet="shared"].hide'));

        $essential = $getSnippet('Essential script (before consent)');
        $this->assertStringContainsString('/mautic-essential.js', $essential);
        $this->assertStringContainsString("dispatchEvent('mauticEssentialReady')", $essential);
        $this->assertStringNotContainsString('/mautic-tracking.js', $essential);
        $this->assertStringNotContainsString('/mtc.js', $essential);
        $this->assertStringNotContainsString('MauticTrackingObject', $essential);
        $this->assertStringNotContainsString('pageview', $essential);

        $tracking = $getSnippet('Tracking add-on (after consent)', 'independent');
        $this->assertStringContainsString('/mautic-tracking.js', $tracking);
        $this->assertStringContainsString("d.addEventListener('mauticEssentialReady',enableTracking)", $tracking);
        $this->assertStringContainsString('w.MauticJS.runtimeReady !== true', $tracking);
        $this->assertStringContainsString("w['MauticTrackingObject']=n", $tracking);
        $this->assertStringContainsString("w[n]('send','pageview')", $tracking);
        $this->assertStringContainsString("a.id='mautic-tracking-script'", $tracking);
        $this->assertStringContainsString("d.getElementById('mautic-tracking-script')", $tracking);
        $this->assertStringNotContainsString('/mautic-essential.js', $tracking);
        $this->assertStringNotContainsString('/mtc.js', $tracking);
        $this->assertStringNotContainsString('MauticFocusUseMauticTrackingConsent', $tracking);

        $full = $getSnippet('Full tracking', 'independent');
        $this->assertSame(1, substr_count($full, '/mautic-essential.js'));
        $this->assertSame(1, substr_count($full, '/mautic-tracking.js'));
        $this->assertStringNotContainsString('/mtc.js', $full);
        $this->assertStringContainsString('a.onload=function()', $full);
        $this->assertStringContainsString('s.src=r', $full);
        $this->assertStringContainsString("mt('send', 'pageview');", $full);
        $this->assertStringNotContainsString('MauticFocusUseMauticTrackingConsent', $full);

        $essentialPosition = strpos($full, '/mautic-essential.js');
        $trackingPosition  = strpos($full, '/mautic-tracking.js');
        $this->assertNotFalse($essentialPosition);
        $this->assertNotFalse($trackingPosition);
        $this->assertLessThan($trackingPosition, $essentialPosition);

        $sharedTracking = $getSnippet('Tracking add-on (after consent)', 'shared');
        $this->assertStringContainsString('w.MauticFocusUseMauticTrackingConsent=true;', $sharedTracking);
        $this->assertStringContainsString("w.MauticJS.dispatchEvent('mautic:tracking-enabled');", $sharedTracking);
        $this->assertLessThan(
            strpos($sharedTracking, 'w.MauticJS.trackingEnabled === true'),
            strpos($sharedTracking, 'w.MauticFocusUseMauticTrackingConsent=true;')
        );

        $temporaryPath = tempnam(sys_get_temp_dir(), 'mautic-shared-tracking-');
        $this->assertNotFalse($temporaryPath);
        file_put_contents($temporaryPath, $sharedTracking);

        try {
            $process = new Process([
                'node',
                __DIR__.'/../../Fixtures/shared-focus-consent-runtime.js',
                $temporaryPath,
            ]);
            $process->mustRun();
            $this->assertSame('', $process->getErrorOutput());
        } finally {
            unlink($temporaryPath);
        }

        $sharedFull = $getSnippet('Full tracking', 'shared');
        $this->assertStringContainsString('w.MauticFocusUseMauticTrackingConsent=true;', $sharedFull);
        $this->assertStringContainsString("mt('send', 'pageview');", $sharedFull);
        $this->assertLessThan(
            strpos($sharedFull, "w['MauticTrackingObject']=n"),
            strpos($sharedFull, 'w.MauticFocusUseMauticTrackingConsent=true;')
        );

        $this->assertCount(0, $crawler->filter('pre:contains("/mtc.js")'));
    }

    public function testSharedFocusConsentSettingPersists(): void
    {
        $this->saveSharedFocusConsentSetting(false);

        try {
            $this->saveSharedFocusConsentSetting(true);

            $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=trackingconfig');

            self::assertResponseIsSuccessful();
            $this->assertNotNull($crawler->filter('#config_trackingconfig_focus_uses_mautic_tracking_consent_1')->attr('checked'));
            $this->assertNull($crawler->filter('#config_trackingconfig_focus_uses_mautic_tracking_consent_0')->attr('checked'));
            $this->assertCount(2, $crawler->filter('[data-focus-consent-snippet="shared"]:not(.hide)'));
            $this->assertCount(2, $crawler->filter('[data-focus-consent-snippet="independent"].hide'));
        } finally {
            $this->saveSharedFocusConsentSetting(false);
        }
    }

    private function saveSharedFocusConsentSetting(bool $sharedConsent): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=trackingconfig');
        $form    = $crawler->selectButton('config[buttons][apply]')->form();
        $form->setValues([
            'config[trackingconfig][focus_uses_mautic_tracking_consent]' => $sharedConsent ? '1' : '0',
            'config[coreconfig][site_url]'                               => 'https://mautic.test',
            'config[leadconfig][contact_columns]'                        => ['name', 'email', 'id'],
            'config[companyconfig][company_columns]'                     => ['companyname', 'companyemail', 'companywebsite', 'score', 'leadcount', 'id'],
        ]);

        $crawler = $this->client->submit($form);
        self::assertResponseIsSuccessful();
        $errors = $crawler->filter('.form-group.has-error .alert-danger')->each(static fn ($node): string => $node->text());
        $this->assertSame([], $errors);
    }
}
