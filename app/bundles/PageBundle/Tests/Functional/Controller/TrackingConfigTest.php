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

        $legacy = $getSnippet('Legacy tracking script');
        Assert::assertStringContainsString('/mtc.js', $legacy);
        Assert::assertStringContainsString("w['MauticTrackingObject']=n", $legacy);
        Assert::assertStringContainsString("mt('send', 'pageview');", $legacy);
        Assert::assertStringNotContainsString('/mautic-essential.js', $legacy);
        Assert::assertStringNotContainsString('/mautic-tracking.js', $legacy);

        $essential = $getSnippet('Essential script');
        Assert::assertStringContainsString('/mautic-essential.js', $essential);
        Assert::assertStringNotContainsString('/mautic-tracking.js', $essential);
        Assert::assertStringNotContainsString('/mtc.js', $essential);

        $tracking = $getSnippet('Tracking add-on');
        Assert::assertStringContainsString('/mautic-tracking.js', $tracking);
        Assert::assertStringContainsString("w['MauticTrackingObject']=n", $tracking);
        Assert::assertStringContainsString("mt('send', 'pageview');", $tracking);
        Assert::assertStringNotContainsString('/mautic-essential.js', $tracking);
        Assert::assertStringNotContainsString('/mtc.js', $tracking);

        $full = $getSnippet('Essential and tracking scripts');
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
    }
}
