<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class TrackingConfigTest extends MauticMysqlTestCase
{
    public function testTrackingScriptOptionsAreRendered(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=trackingconfig');

        self::assertResponseIsSuccessful();

        $getSnippet = static function (string $ariaLabel) use ($crawler): string {
            $snippet = $crawler->filter(sprintf('pre[aria-label="%s"]', $ariaLabel));
            Assert::assertCount(1, $snippet);

            return $snippet->text();
        };

        $essential = $getSnippet('Essential script (before consent)');
        Assert::assertStringContainsString('/mautic-essential.js', $essential);
        Assert::assertStringContainsString("dispatchEvent('mauticEssentialReady')", $essential);
        Assert::assertStringNotContainsString('/mautic-tracking.js', $essential);
        Assert::assertStringNotContainsString('/mtc.js', $essential);
        Assert::assertStringNotContainsString('MauticTrackingObject', $essential);
        Assert::assertStringNotContainsString('pageview', $essential);

        $tracking = $getSnippet('Tracking add-on (after consent)');
        Assert::assertStringContainsString('/mautic-tracking.js', $tracking);
        Assert::assertStringContainsString("d.addEventListener('mauticEssentialReady',enableTracking)", $tracking);
        Assert::assertStringContainsString('w.MauticJS.runtimeReady !== true', $tracking);
        Assert::assertStringContainsString("w['MauticTrackingObject']=n", $tracking);
        Assert::assertStringContainsString("w[n]('send','pageview')", $tracking);
        Assert::assertStringContainsString("a.id='mautic-tracking-script'", $tracking);
        Assert::assertStringContainsString("d.getElementById('mautic-tracking-script')", $tracking);
        Assert::assertStringNotContainsString('/mautic-essential.js', $tracking);
        Assert::assertStringNotContainsString('/mtc.js', $tracking);

        $full = $getSnippet('Full tracking');
        Assert::assertSame(1, substr_count($full, '/mautic-essential.js'));
        Assert::assertSame(1, substr_count($full, '/mautic-tracking.js'));
        Assert::assertStringNotContainsString('/mtc.js', $full);
        Assert::assertStringContainsString('a.onload=function()', $full);
        Assert::assertStringContainsString('s.src=r', $full);
        Assert::assertStringContainsString("mt('send', 'pageview');", $full);

        $essentialPosition = strpos($full, '/mautic-essential.js');
        $trackingPosition  = strpos($full, '/mautic-tracking.js');
        Assert::assertNotFalse($essentialPosition);
        Assert::assertNotFalse($trackingPosition);
        Assert::assertLessThan($trackingPosition, $essentialPosition);

        Assert::assertCount(0, $crawler->filter('pre:contains("/mtc.js")'));
    }
}
