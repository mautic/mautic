<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

class Version
{
    private string $version;
    private array $license;
    private string $homepage;
    private string $issues;
    private \DateTimeInterface $time;
    private array $require;
    private array $keywords;

    public function __construct(string $version, array $license, \DateTimeInterface $time, string $homepage, string $issues, array $require, array $keywords)
    {
        $this->version  = $version;
        $this->license  = $license;
        $this->time     = $time;
        $this->homepage = $homepage;
        $this->issues   = $issues;
        $this->require  = $require;
        $this->keywords = $keywords;
    }

    public static function fromArray(array $array): Version
    {
        return new self(
            $array['version'],
            $array['license'],
            new \DateTimeImmutable($array['time']),
            $array['homepage'],
            $array['support']['issues'] ?? '',
            (array) $array['require'],
            (array) $array['keywords']
        );
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getLicense(): array
    {
        return $this->license;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function getIssues(): string
    {
        return $this->issues;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function getRequire(): array
    {
        return $this->require;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }
}
