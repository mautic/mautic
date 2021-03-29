<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class Version
{
    public string $version;
    public array $license;
    public string $homepage;
    public string $issues;
    public \DateTimeInterface $time;
    public array $require;
    public array $keywords;

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
}
