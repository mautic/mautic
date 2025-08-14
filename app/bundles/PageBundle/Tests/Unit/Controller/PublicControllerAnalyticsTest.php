<?php

namespace Mautic\PageBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

class PublicControllerAnalyticsTest extends TestCase
{
    /**
     * @dataProvider analyticsScenarioProvider
     */
    public function testAnalyticsInjection(string $analytics, string $footerAnalytics, bool $noIndex, array $expectedStrings): void
    {
        $content = '<html><head></head><body>test content</body></html>';

        if (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        if (!empty($footerAnalytics)) {
            $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);
        }

        if ($noIndex) {
            $content = str_replace('</head>', "<meta name=\"robots\" content=\"noindex\">\n</head>", $content);
        }

        foreach ($expectedStrings as $expected) {
            $this->assertStringContainsString($expected, $content);
        }
    }

    public static function analyticsScenarioProvider(): array
    {
        return [
            'head only' => [
                '<script>head</script>',
                '',
                false,
                ['<script>head</script>', 'test content'],
            ],
            'footer only' => [
                '',
                '<script>footer</script>',
                false,
                ['<script>footer</script>', 'test content'],
            ],
            'both scripts' => [
                '<script>head</script>',
                '<script>footer</script>',
                false,
                ['<script>head</script>', '<script>footer</script>', 'test content'],
            ],
            'noindex only' => [
                '',
                '',
                true,
                ['<meta name="robots" content="noindex">', 'test content'],
            ],
            'all features' => [
                '<script>head</script>',
                '<script>footer</script>',
                true,
                ['<script>head</script>', '<script>footer</script>', '<meta name="robots" content="noindex">', 'test content'],
            ],
            'no analytics' => [
                '',
                '',
                false,
                ['test content'],
            ],
        ];
    }
}
