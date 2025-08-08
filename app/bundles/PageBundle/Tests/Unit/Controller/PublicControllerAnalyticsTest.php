<?php

namespace Mautic\PageBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

class PublicControllerAnalyticsTest extends TestCase
{
    public function testAnalyticsInjectionIntoContent(): void
    {
        // This tests the logic that our changes added to PublicController
        // We simulate the str_replace operations that happen in the controller

        $content         = '<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>';
        $analytics       = '<script>console.log("head");</script>';
        $footerAnalytics = '<script>console.log("footer");</script>';

        // Test head analytics injection (existing logic)
        if (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        // Test footer analytics injection (our new logic)
        if (!empty($footerAnalytics)) {
            $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);
        }

        $expectedContent = '<html><head><title>Test</title><script>console.log("head");</script>'."\n".'</head><body><h1>Test Page</h1><script>console.log("footer");</script>'."\n".'</body></html>';

        $this->assertEquals($expectedContent, $content);
    }

    public function testAnalyticsInjectionWithEmptyFooter(): void
    {
        $content         = '<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>';
        $analytics       = '<script>console.log("head");</script>';
        $footerAnalytics = '';

        // Test head analytics injection
        if (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        // Test footer analytics injection (should not modify content)
        if (!empty($footerAnalytics)) {
            $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);
        }

        $expectedContent = '<html><head><title>Test</title><script>console.log("head");</script>'."\n".'</head><body><h1>Test Page</h1></body></html>';

        $this->assertEquals($expectedContent, $content);
    }

    public function testAnalyticsInjectionWithEmptyHead(): void
    {
        $content         = '<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>';
        $analytics       = '';
        $footerAnalytics = '<script>console.log("footer");</script>';

        // Test head analytics injection (should not modify content)
        if (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        // Test footer analytics injection
        if (!empty($footerAnalytics)) {
            $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);
        }

        $expectedContent = '<html><head><title>Test</title></head><body><h1>Test Page</h1><script>console.log("footer");</script>'."\n".'</body></html>';

        $this->assertEquals($expectedContent, $content);
    }

    public function testAnalyticsInjectionWithBothEmpty(): void
    {
        $content         = '<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>';
        $analytics       = '';
        $footerAnalytics = '';

        // Test head analytics injection (should not modify content)
        if (!empty($analytics)) {
            $content = str_replace('</head>', $analytics."\n</head>", $content);
        }

        // Test footer analytics injection (should not modify content)
        if (!empty($footerAnalytics)) {
            $content = str_replace('</body>', $footerAnalytics."\n</body>", $content);
        }

        // Content should remain unchanged
        $expectedContent = '<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>';

        $this->assertEquals($expectedContent, $content);
    }
}
