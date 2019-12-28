<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\DTO;

class Version
{
    private $version;
    private $license;
    private $homepage;
    private $issues;
    private $time;
    private $require;
    private $keywords;

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
