<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper\DTO;

final readonly class EmailPreviewContentResult
{
    public function __construct(
        private string $content,
        private bool $renderedFromTheme,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isRenderedFromTheme(): bool
    {
        return $this->renderedFromTheme;
    }
}
