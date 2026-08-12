<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[Group('database')]
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
        $this->assertStringContainsString('/mautic-essential.js', $essential);
        $this->assertStringContainsString("dispatchEvent('mauticEssentialReady')", $essential);
        $this->assertStringNotContainsString('/mautic-tracking.js', $essential);
        $this->assertStringNotContainsString('/mtc.js', $essential);
        $this->assertStringNotContainsString('MauticTrackingObject', $essential);
        $this->assertStringNotContainsString('pageview', $essential);

        $tracking = $getSnippet('Tracking add-on (after consent)');
        $this->assertStringContainsString('/mautic-tracking.js', $tracking);
        $this->assertStringContainsString("d.addEventListener('mauticEssentialReady',enableTracking)", $tracking);
        $this->assertStringContainsString('w.MauticJS.runtimeReady !== true', $tracking);
        $this->assertStringContainsString("w['MauticTrackingObject']=n", $tracking);
        $this->assertStringContainsString("w[n]('send','pageview')", $tracking);
        $this->assertStringContainsString("a.id='mautic-tracking-script'", $tracking);
        $this->assertStringContainsString("d.getElementById('mautic-tracking-script')", $tracking);
        $this->assertStringNotContainsString('/mautic-essential.js', $tracking);
        $this->assertStringNotContainsString('/mtc.js', $tracking);

        $full = $getSnippet('Full tracking');
        $this->assertSame(1, substr_count($full, '/mautic-essential.js'));
        $this->assertSame(1, substr_count($full, '/mautic-tracking.js'));
        $this->assertStringNotContainsString('/mtc.js', $full);
        $this->assertStringContainsString('a.onload=function()', $full);
        $this->assertStringContainsString('s.src=r', $full);
        $this->assertStringContainsString("mt('send', 'pageview');", $full);

        $essentialPosition = strpos($full, '/mautic-essential.js');
        $trackingPosition  = strpos($full, '/mautic-tracking.js');
        $this->assertNotFalse($essentialPosition);
        $this->assertNotFalse($trackingPosition);
        $this->assertLessThan($trackingPosition, $essentialPosition);

        $this->assertCount(0, $crawler->filter('pre:contains("/mtc.js")'));
    }
}
