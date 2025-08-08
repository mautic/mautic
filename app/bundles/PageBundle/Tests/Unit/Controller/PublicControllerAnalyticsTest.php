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
}
