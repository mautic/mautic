<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

trait QuickFilterSearchTrait
{
    /**
     * @param string[] $tokens
     */
    private function stripQuickFilterTokensFromSearch(string $search, array $tokens): string
    {
        $search = trim($search);
        if ('' === $search || [] === $tokens) {
            return $search;
        }

        $terms          = preg_split('/\s+/', $search) ?: [];
        $tokensToRemove = array_fill_keys(array_filter(array_unique($tokens)), true);
        $terms          = array_values(array_filter($terms, static fn (string $term): bool => !isset($tokensToRemove[$term])));

        return implode(' ', $terms);
    }
}
