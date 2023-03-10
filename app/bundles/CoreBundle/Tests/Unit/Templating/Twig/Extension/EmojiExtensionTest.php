<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Templating\Twig\Extension\EmojiExtension;
use PHPUnit\Framework\TestCase;

final class EmojiExtensionTest extends TestCase
{
    public function testItContainsAtLeastOneFunction(): void
    {
        $extension = new EmojiExtension(new EmojiHelper());

        $this->assertGreaterThan(0, $extension->getFunctions());
    }

    public function testToHtml(): void
    {
        $extension = new EmojiExtension(new EmojiHelper());

        $text = 'This is example text';

        $this->assertSame($text, $extension->toHtml($text));
    }
}
