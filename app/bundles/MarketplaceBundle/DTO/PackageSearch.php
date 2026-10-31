<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final readonly class PackageSearch
{
    public string $query;

    public string $type;

    public function __construct(string $search)
    {
        $type = '';
        $query = preg_replace_callback(
            '/(?<!\S)type:(mautic-plugin|mautic-theme|mautic-resource)(?!\S)/',
            static function (array $matches) use (&$type): string {
                // The API accepts one type; the last selection wins for older saved searches.
                $type = $matches[1];

                return '';
            },
            $search
        );

        $this->query = trim($query);
        $this->type  = $type;
    }
}
