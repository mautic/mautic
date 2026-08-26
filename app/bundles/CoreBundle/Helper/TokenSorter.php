<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TokenSorter
{
    private const int CATEGORY_CONTACT   = 10;

    private const int CATEGORY_COMPANY   = 20;

    private const int CATEGORY_OWNER     = 30;

    private const int CATEGORY_PAGE_LINK = 40;

    private const int CATEGORY_DWC       = 50;

    private const int CATEGORY_FOCUS     = 60;

    private const int CATEGORY_ASSET     = 70;

    private const int CATEGORY_THIS_PAGE = 80;

    private const int CATEGORY_EMAIL     = 90;

    private const int CATEGORY_OTHER     = 999;

    /**
     * @var array<string, int>
     */
    private const array TOKEN_KEY_PATTERNS = [
        '{contactfield=company' => self::CATEGORY_COMPANY,
        '{contactfield='        => self::CATEGORY_CONTACT,
        '{ownerfield='          => self::CATEGORY_OWNER,
        '{pagelink='            => self::CATEGORY_PAGE_LINK,
        '{dwc='                 => self::CATEGORY_DWC,
        '{focus='               => self::CATEGORY_FOCUS,
        '{assetlink='           => self::CATEGORY_ASSET,
    ];

    /**
     * Priority fields within Contact category (these appear first).
     *
     * @var array<string, int>
     */
    private const array CONTACT_PRIORITY = [
        '{contactfield=firstname}' => 1,
        '{contactfield=lastname}'  => 2,
        '{contactfield=title}'     => 3,
    ];

    /**
     * Label prefix patterns mapped to categories (built from translations).
     *
     * @var array<string, int>
     */
    private array $labelPrefixCategories;

    public function __construct(TranslatorInterface $translator)
    {
        $this->labelPrefixCategories = [
            $translator->trans('mautic.page.token.thispage').': ' => self::CATEGORY_THIS_PAGE,
            $translator->trans('mautic.email.email').': '         => self::CATEGORY_EMAIL,
        ];
    }

    /**
     * @param array<string, string> $tokens
     *
     * @return array<string, string>
     */
    public function sortTokens(array $tokens): array
    {
        uksort($tokens, function (string $keyA, string $keyB) use ($tokens): int {
            $labelA = $tokens[$keyA];
            $labelB = $tokens[$keyB];

            $catA = $this->getTokenCategory($keyA, $labelA);
            $catB = $this->getTokenCategory($keyB, $labelB);

            if ($catA !== $catB) {
                return $catA <=> $catB;
            }

            // prioritize firstname, lastname, title in contact category
            if (self::CATEGORY_CONTACT === $catA) {
                $prioA = self::CONTACT_PRIORITY[$keyA] ?? PHP_INT_MAX;
                $prioB = self::CONTACT_PRIORITY[$keyB] ?? PHP_INT_MAX;

                if ($prioA !== $prioB) {
                    return $prioA <=> $prioB;
                }
            }

            return strcasecmp($labelA, $labelB);
        });

        return $tokens;
    }

    private function getTokenCategory(string $tokenKey, string $label): int
    {
        // check token key patterns
        foreach (self::TOKEN_KEY_PATTERNS as $pattern => $category) {
            if (str_starts_with($tokenKey, $pattern)) {
                return $category;
            }
        }

        // check label prefix patterns (match email of lp specific tokens)
        foreach ($this->labelPrefixCategories as $prefix => $category) {
            if (str_starts_with($label, $prefix)) {
                return $category;
            }
        }

        return self::CATEGORY_OTHER;
    }
}
