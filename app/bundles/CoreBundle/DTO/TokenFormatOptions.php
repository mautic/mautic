<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DTO;

final readonly class TokenFormatOptions
{
    public function __construct(
        public string $translationKey,
        public TokenLabelFormat $format = TokenLabelFormat::SIMPLE_PREFIX,
        public ?string $tokenIdPattern = null,
    ) {
    }

    /**
     * Creates format options for simple prefix labels like "Form: My Form".
     */
    public static function simplePrefix(string $translationKey): self
    {
        return new self($translationKey, TokenLabelFormat::SIMPLE_PREFIX);
    }

    /**
     * Creates format options for link tokens with ID like "a:Page: my-alias (123)".
     *
     * @param string $translationKey The translation key for the prefix (e.g., 'mautic.page.page')
     * @param string $tokenIdPattern Regex pattern to extract ID from token (e.g., 'pagelink=(\d+)')
     */
    public static function linkWithId(string $translationKey, string $tokenIdPattern): self
    {
        return new self($translationKey, TokenLabelFormat::LINK_WITH_ID, $tokenIdPattern);
    }
}
