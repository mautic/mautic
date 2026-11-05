<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Symfony\Component\Intl\Locales;

final class SegmentPublicNameLocaleNormalizer
{
    public function normalize(string $locale): string
    {
        $locale = trim($locale);
        if ('' === $locale || strlen($locale) > 16) {
            throw new \InvalidArgumentException('Invalid locale.');
        }

        $canonical = \Locale::canonicalize(str_replace('-', '_', $locale));
        if (null === $canonical || false === $canonical || !Locales::exists($canonical)) {
            throw new \InvalidArgumentException('Unsupported locale.');
        }

        return $canonical;
    }
}
