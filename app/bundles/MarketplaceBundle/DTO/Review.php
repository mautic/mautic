<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class Review
{
    public function __construct(
        public string $username,
        public int $rating,
        public string $review
    ) {
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['name'],
            (int) $array['rating'],
            $array['review']
        );
    }
}
