<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class EmojiExtension extends AbstractExtension
{
    public function __construct(
        protected EmojiHelper $emojiHelper,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('emoji_to_html', [$this, 'toHtml'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Convert to html.
     *
     * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
     */
    public function toHtml(string $text, string $from = 'emoji'): string
    {
        return $this->emojiHelper->toHtml($text, $from);
    }
}
