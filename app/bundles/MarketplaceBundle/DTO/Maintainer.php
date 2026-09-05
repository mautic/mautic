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

    /**
     * @param array<string, mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array['name'],
            $array['avatar_url']
        );
    }
}
