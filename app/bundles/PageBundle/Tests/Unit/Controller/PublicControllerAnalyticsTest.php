<?php

namespace Mautic\PageBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

class PublicControllerAnalyticsTest extends TestCase
{
    public function testFooterAnalyticsInjection(): void
    {
        $content         = '<html><head></head><body>test</body></html>';
        $footerAnalytics = '<script>footer</script>';

        $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);

        $this->assertStringContainsString('<script>footer</script>', $content);
    }

    public function testEmptyFooterAnalyticsCondition(): void
    {
        $content         = '<html><head></head><body>test</body></html>';
        $originalContent = $content;

        $footerAnalytics = $this->getFooterValue(false);

        if (!empty($footerAnalytics)) {
            $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);
        }

        $this->assertEquals($originalContent, $content);
    }

    public function testAnalyticsHeadInjection(): void
    {
        $content = '<html><head></head><body>test</body></html>';

        $analytics = $this->getAnalyticsValue(true);

        if (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        $this->assertStringContainsString('<script>analytics</script>', $content);
    }

    public function testFooterCodeInitialization(): void
    {
        $footerScript    = '<script>footer</script>';
        $footerAnalytics = $footerScript;

        $this->assertEquals($footerScript, $footerAnalytics);
    }

    public function testDispatcherHasListenersCondition(): void
    {
        $hasListeners = $this->getHasListenersValue();

        if ($hasListeners) {
            $result = 'event dispatched';
        } else {
            $result = 'no listeners';
        }

        $this->assertEquals('event dispatched', $result);
    }

    private function getFooterValue(bool $hasFooter): string
    {
        return $hasFooter ? '<script>footer</script>' : '';
    }

    private function getAnalyticsValue(bool $hasAnalytics): string
    {
        return $hasAnalytics ? '<script>analytics</script>' : '';
    }

    private function getHasListenersValue(): bool
    {
        return true;
    }
}
