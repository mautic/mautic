<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class Version
{
    public string $version;
    public array $license;
    public string $homepage;
    public string $issues;
    public string $wiki;
    public \DateTimeInterface $time;
    public array $require;
    public array $keywords;
    public ?string $type;
    public ?string $directoryName;
    public ?string $displayName;

    public function __construct(string $version, array $license, \DateTimeInterface $time, string $homepage, string $issues, string $wiki, array $require, array $keywords, ?string $type, ?string $directoryName, ?string $displayName)
    {
        $this->version       = $version;
        $this->license       = $license;
        $this->time          = $time;
        $this->homepage      = $homepage;
        $this->issues        = $issues;
        $this->wiki          = $wiki;
        $this->require       = $require;
        $this->keywords      = $keywords;
        $this->type          = $type;
        $this->directoryName = $directoryName;
        $this->displayName   = $displayName;
    }

    public static function fromArray(array $array): Version
    {
        return new self(
            $array['version'],
            $array['license'],
            new \DateTimeImmutable($array['time']),
            $array['homepage'],
            $array['support']['issues'] ?? '',
            $array['support']['wiki'] ?? '',
            $array['require'] ?? [],
            $array['keywords'] ?? [],
            $array['type'] ?? null,
            $array['extra']['install-directory-name'] ?? null,
            $array['extra']['display-name'] ?? null
        );
    }

    /**
     * Consider a version stable if it is in SemVer fomrat "d.d.d".
     */
    public function isStable(): bool
    {
        return 1 === preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $this->version);
    }

    /**
     * Consider a version pre-release if it is in fomrat "d.d.d-s".
     */
    public function isPreRelease(): bool
    {
        return 1 === preg_match('#^(\d+\.)?(\d+\.)?(\d+)(-[a-z0-9]+)?$#i', $this->version);
    }
}
