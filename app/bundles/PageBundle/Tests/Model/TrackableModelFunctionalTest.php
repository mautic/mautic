<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class TrackableModelFunctionalTest extends MauticMysqlTestCase
{
    private TrackableModel $trackableModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackableModel = self::getContainer()->get(TrackableModel::class);
    }

    /**
     * @param string[] $expectedTrackedUrls
     * @param string[] $expectedUntrackedHtml
     */
    #[DataProvider('disableTrackingDataProvider')]
    public function testDisableTrackingAttributeWorks(string $content, array $expectedTrackedUrls, array $expectedUntrackedHtml): void
    {
        [$newContent, $trackableTokens] = $this->trackableModel->parseContentForTrackables($content, [], 'email', 1);

        $this->assertCount(count($expectedTrackedUrls), $trackableTokens);

        $trackableUrls = [];
        foreach ($trackableTokens as $trackable) {
            $trackableUrls[] = $trackable->getRedirect()->getUrl();
        }

        // Sort both arrays to avoid issues with order.
        sort($expectedTrackedUrls);
        sort($trackableUrls);

        $this->assertSame($expectedTrackedUrls, $trackableUrls);

        foreach ($expectedUntrackedHtml as $untrackedHtml) {
            $this->assertStringContainsString($untrackedHtml, (string) $newContent);
        }

        if ([] === $expectedTrackedUrls) {
            $this->assertStringNotContainsString('{trackable=', (string) $newContent);
        } else {
            $this->assertStringContainsString('{trackable=', (string) $newContent);
        }
    }

    public function testParsingWithoutPersistingLeavesNoTrackableOrRedirectRows(): void
    {
        $content = '<a href="https://example.com/not-tracked">Offer</a>';

        [$newContent, $trackableTokens] = $this->trackableModel->parseContentForTrackablesWithoutSaving($content, [], 'email', 2);

        // The tokens are still built, so the links can be replaced and get UTM tags or shortening
        $this->assertCount(1, $trackableTokens);
        $this->assertStringContainsString('{trackable=', (string) $newContent);
        $this->assertSame('https://example.com/not-tracked', reset($trackableTokens)->getRedirect()->getUrl());

        $this->em->clear();
        $this->assertSame([], $this->em->getRepository(Trackable::class)->findBy(['channel' => 'email', 'channelId' => 2]));
        $this->assertSame([], $this->em->getRepository(Redirect::class)->findBy(['url' => 'https://example.com/not-tracked']));

        // The next parse saves again
        $this->trackableModel->parseContentForTrackables($content, [], 'email', 2);
        $this->em->clear();
        $this->assertCount(1, $this->em->getRepository(Trackable::class)->findBy(['channel' => 'email', 'channelId' => 2]));
    }

    public function testParsingWithoutPersistingAndWithoutAChannelLeavesNoRedirectRows(): void
    {
        $content = '<a href="https://example.com/not-tracked-redirect">Offer</a>';

        // Without a channel the links are plain redirects, which must not be saved either
        [, $trackableTokens] = $this->trackableModel->parseContentForTrackablesWithoutSaving($content);

        $this->assertCount(1, $trackableTokens);
        $this->assertInstanceOf(Redirect::class, reset($trackableTokens));

        $this->em->clear();
        $this->assertSame([], $this->em->getRepository(Redirect::class)->findBy(['url' => 'https://example.com/not-tracked-redirect']));
    }

    /**
     * @return iterable<string, array<int, string|string[]>>
     */
    public static function disableTrackingDataProvider(): iterable
    {
        yield 'HTML5 data attribute' => [
            <<<HTML
<a href="https://mautic.org">Mautic</a>
<a href="https://google.com" data-mautic-disable-tracking="true">Google</a>
<a href="https://another-link.com">Another Link</a>
HTML,
            ['https://mautic.org', 'https://another-link.com'],
            ['<a href="https://google.com" data-mautic-disable-tracking="true">Google</a>'],
        ];

        yield 'HTML5 data attribute with false value is still tracked' => [
            <<<HTML
<a href="https://mautic.org">Mautic</a>
<a href="https://google.com" data-mautic-disable-tracking="false">Google</a>
<a href="https://another-link.com">Another Link</a>
HTML,
            ['https://mautic.org', 'https://google.com', 'https://another-link.com'],
            [],
        ];

        yield 'Deprecated attribute' => [
            <<<HTML
<a href="https://mautic.org">Mautic</a>
<a href="https://deprecated.com" mautic:disable-tracking>Deprecated</a>
HTML,
            ['https://mautic.org'],
            ['<a href="https://deprecated.com" mautic:disable-tracking>Deprecated</a>'],
        ];

        yield 'Both attributes on same link' => [
            <<<HTML
<a href="https://mautic.org">Mautic</a>
<a href="https://google.com" data-mautic-disable-tracking="true" mautic:disable-tracking>Google</a>
HTML,
            ['https://mautic.org'],
            ['<a href="https://google.com" data-mautic-disable-tracking="true" mautic:disable-tracking>Google</a>'],
        ];

        yield 'Both attributes on different links' => [
            <<<HTML
<a href="https://mautic.org">Mautic</a>
<a href="https://google.com" data-mautic-disable-tracking="true">Google</a>
<a href="https://deprecated.com" mautic:disable-tracking>Deprecated</a>
HTML,
            ['https://mautic.org'],
            [
                '<a href="https://google.com" data-mautic-disable-tracking="true">Google</a>',
                '<a href="https://deprecated.com" mautic:disable-tracking>Deprecated</a>',
            ],
        ];

        yield 'No tracking disabled' => [
            <<<HTML
<a href="https://mautic.org">Mautic</a>
<a href="https://google.com">Google</a>
HTML,
            ['https://mautic.org', 'https://google.com'],
            [],
        ];
    }
}
