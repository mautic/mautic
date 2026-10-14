<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Twig;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('marketplace_markdown', $this->render(...))];
    }

    public function render(mixed $markdown): string
    {
        if (!is_string($markdown) || '' === trim($markdown)) {
            return '';
        }

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);

        return (string) $converter->convert($markdown);
    }
}
