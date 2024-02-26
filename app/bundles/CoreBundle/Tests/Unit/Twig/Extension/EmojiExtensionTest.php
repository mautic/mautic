<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Twig\Extension\EmojiExtension;
use PHPUnit\Framework\TestCase;

final class EmojiExtensionTest extends TestCase
{
    public function testItContainsAtLeastOneFunction(): void
    {
        $extension = new EmojiExtension(new EmojiHelper()); /** @phpstan-ignore-line EmojiExtension is deprecated */
        $this->assertGreaterThan(0, $extension->getFunctions());
    }

    public function testToHtml(): void
    {
        $extension = new EmojiExtension(new EmojiHelper()); /** @phpstan-ignore-line EmojiExtension is deprecated */
        $text      = 'This is example text';

        $this->assertSame($text, $extension->toHtml($text)); /** @phpstan-ignore-line toHtml() is deprecated */
    }
}
