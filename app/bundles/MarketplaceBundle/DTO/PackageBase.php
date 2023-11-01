<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class PackageBase
{
    /**
     * Original name in format "vendor/name".
     */
    public string $name;
    public ?string $displayName;
    public string $url;
    public string $repository;
    public string $description;
    public int $downloads;
    public int $favers;

    /**
     * E.g. mautic-plugin.
     */
    public ?string $type;

    public function __construct(string $name, string $url, string $repository, string $description, int $downloads, int $favers, ?string $type, ?string $displayName = null)
    {
        $this->name        = $name;
        $this->displayName = $displayName;
        $this->url         = $url;
        $this->repository  = $repository;
        $this->description = $description;
        $this->downloads   = $downloads;
        $this->favers      = $favers;
        $this->type        = $type;
    }

    public static function fromArray(array $array)
    {
        return new self(
            $array['name'],
            $array['url'],
            $array['repository'],
            $array['description'],
            (int) $array['downloads'],
            (int) $array['favers'],
            $array['type'] ?? null,
            $array['display_name'] ?? null
        );
    }

    /**
     * Just an alias to getName(). Used in Mautic helpers.
     */
    public function getId(): string
    {
        return $this->name;
    }

    /**
     * Used in Mautic helpers.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getPackageName(): string
    {
        [, $packageName] = explode('/', $this->name);

        return $packageName;
    }

    public function getHumanPackageName(): string
    {
        if ($this->displayName) {
            return $this->displayName;
        }

        return utf8_ucwords(str_replace('-', ' ', $this->getPackageName()));
    }

    public function getVendorName(): string
    {
        [$vendor] = explode('/', $this->name);

        return $vendor;
    }
}
