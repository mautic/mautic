<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\Helper;

use MauticPlugin\GrapesJsBuilderBundle\Helper\MjmlContentHelper;
use PHPUnit\Framework\TestCase;

final class MjmlContentHelperTest extends TestCase
{
    public function testIsMjmlDetectsMjmlMarkup(): void
    {
        $this->assertTrue(MjmlContentHelper::isMjml('<mjml><mj-body></mj-body></mjml>'));
        $this->assertFalse(MjmlContentHelper::isMjml('<html><body></body></html>'));
    }

    public function testToHtmlConvertsMjmlToHtml(): void
    {
        $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Hello</mj-text></mj-column></mj-section></mj-body></mjml>';
        $html = MjmlContentHelper::toHtml($mjml);

        $this->assertNotNull($html);
        $this->assertStringNotContainsString('<mjml>', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function testToHtmlReturnsNullForNonMjmlContent(): void
    {
        $this->assertNull(MjmlContentHelper::toHtml('<html><body></body></html>'));
    }
}
