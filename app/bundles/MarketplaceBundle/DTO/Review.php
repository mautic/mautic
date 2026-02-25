<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class Review
{
    public function __construct(
        public string $username,
        public int $rating,
        public string $review,
    ) {
    }

    /**
     * @param array{rating?: int, name?: string, user?: string, review?: string} $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array['name'] ?? $array['user'] ?? 'Unknown user',
            (int) ($array['rating'] ?? 0),
            $array['review'] ?? ''
        );
    }
}
