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
}
