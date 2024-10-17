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

    /**
     * @param array{name?: string, rating: int, review?: string} $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array['name'] ?? 'Unknown user',
            (int) $array['rating'],
            $array['review'] ?? ''
        );
    }
}
