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

class Package
{
    private $name;
    private $url;
    private $repository;
    private $description;
    private $downloads;
    private $favers;

    public function __construct(string $name, string $url, string $repository, string $description, int $downloads, int $favers)
    {
        $this->name        = $name;
        $this->url         = $url;
        $this->repository  = $repository;
        $this->description = $description;
        $this->downloads   = $downloads;
        $this->favers      = $favers;
    }

    public static function fromArray(array $array)
    {
        return new self(
            $array['name'],
            $array['url'],
            $array['repository'],
            $array['description'],
            (int) $array['downloads'],
            (int) $array['favers']
        );
    }

    /**
     * Just an alias to getName(). Used in Mautic helpers.
     */
    public function getId(): string
    {
        return $this->getName();
    }

    /**
     * Returns original name in format "vendor/name".
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getPackageName(): string
    {
        list(, $packageName) = explode('/', $this->getName());

        return $packageName;
    }

    public function getHumanPackageName(): string
    {
        return utf8_ucfirst(str_replace('-', ' ', $this->getPackageName()));
    }

    public function getVendorName(): string
    {
        list($vendor) = explode('/', $this->getName());

        return $vendor;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDownloads(): int
    {
        return $this->downloads;
    }

    public function getFavers(): int
    {
        return $this->favers;
    }
}
