<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class Maintainer
{
    public function __construct(
        public string $name,
        public string $avatar,
    ) {
    }

    public static function fromArray(array $array): Maintainer
    {
        return new self(
            $array['name'],
            $array['avatar_url']
        );
    }
}
