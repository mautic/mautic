<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\Helper;

use MauticPlugin\GrapesJsBuilderBundle\Helper\MjmlContentHelper;
use PHPUnit\Framework\TestCase;

class MjmlContentHelperTest extends TestCase
{
    public function testIsMjmlDetectsMjmlMarkup(): void
    {
        self::assertTrue(MjmlContentHelper::isMjml('<mjml><mj-body></mj-body></mjml>'));
        self::assertFalse(MjmlContentHelper::isMjml('<html><body></body></html>'));
    }

    public function testToHtmlConvertsMjmlToHtml(): void
    {
        $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Hello</mj-text></mj-column></mj-section></mj-body></mjml>';
        $html = MjmlContentHelper::toHtml($mjml);

        self::assertNotNull($html);
        self::assertStringNotContainsString('<mjml>', $html);
        self::assertStringContainsString('Hello', $html);
    }

    public function testToHtmlReturnsNullForNonMjmlContent(): void
    {
        self::assertNull(MjmlContentHelper::toHtml('<html><body></body></html>'));
    }
}
