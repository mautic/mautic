<?php


namespace Mautic\CoreBundle\Shortener;

interface ShortenerServiceInterface
{
    public function shortenUrl(string $url): string;

    public function isEnabled(): bool;
}
