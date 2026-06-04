<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class TrackableModelFunctionalTest extends MauticMysqlTestCase
{
    private TrackableModel $trackableModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackableModel = self::getContainer()->get('mautic.page.model.trackable');
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
            $this->assertStringContainsString($untrackedHtml, $newContent);
        }

        if (empty($expectedTrackedUrls)) {
            $this->assertStringNotContainsString('{trackable=', $newContent);
        } else {
            $this->assertStringContainsString('{trackable=', $newContent);
        }
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
