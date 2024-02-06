<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Shortener;

interface ShortenerServiceInterface
{
    public function shortenUrl(string $url): string;

    public function isEnabled(): bool;

    public function getPublicName(): string;
}
