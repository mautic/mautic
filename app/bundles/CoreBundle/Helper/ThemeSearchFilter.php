<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Filters installed themes for the themes list search.
 */
final class ThemeSearchFilter
{
    /**
     * @param array<string, array<string, mixed>> $themes
     * @param list<string>                        $scopeCommands
     *
     * @return array<string, array<string, mixed>>
     */
    public function filter(array $themes, string $search, array $scopeCommands, TranslatorInterface $translator): array
    {
        $search = trim($search);
        if ('' === $search) {
            return $themes;
        }

        $parsed  = SearchScopeHelper::parse($search, $scopeCommands);
        $command = $parsed['command'];
        $value   = mb_strtolower(trim($parsed['value']));

        if ('' === $command) {
            return $this->filterStandard($themes, $value);
        }

        $featureCommand = $translator->trans('mautic.core.theme.searchcommand.feature');
        $builderCommand = $translator->trans('mautic.core.theme.searchcommand.builder');

        if ($command === $featureCommand) {
            return $this->filterByFeature($themes, $value);
        }

        if ($command === $builderCommand) {
            return $this->filterByBuilder($themes, $value);
        }

        return $themes;
    }

    /**
     * @param array<string, array<string, mixed>> $themes
     *
     * @return array<string, array<string, mixed>>
     */
    private function filterStandard(array $themes, string $value): array
    {
        if ('' === $value) {
            return $themes;
        }

        return array_filter(
            $themes,
            function (array $theme, string $key) use ($value): bool {
                $haystacks = [
                    mb_strtolower($key),
                    mb_strtolower((string) ($theme['name'] ?? '')),
                    mb_strtolower((string) ($theme['config']['author'] ?? '')),
                ];

                foreach ($haystacks as $haystack) {
                    if (str_contains($haystack, $value)) {
                        return true;
                    }
                }

                return false;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @param array<string, array<string, mixed>> $themes
     *
     * @return array<string, array<string, mixed>>
     */
    private function filterByFeature(array $themes, string $value): array
    {
        if ('' === $value) {
            return $themes;
        }

        return array_filter(
            $themes,
            static function (array $theme) use ($value): bool {
                $features = $theme['config']['features'] ?? [];

                if (!is_array($features)) {
                    return false;
                }

                foreach ($features as $feature) {
                    if ($value === mb_strtolower((string) $feature)) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * @param array<string, array<string, mixed>> $themes
     *
     * @return array<string, array<string, mixed>>
     */
    private function filterByBuilder(array $themes, string $value): array
    {
        if ('' === $value) {
            return $themes;
        }

        return array_filter(
            $themes,
            static function (array $theme) use ($value): bool {
                $builders = $theme['config']['builder'] ?? ['legacy'];

                if (!is_array($builders)) {
                    return false;
                }

                foreach ($builders as $builder) {
                    if ($value === mb_strtolower((string) $builder)) {
                        return true;
                    }
                }

                return false;
            }
        );
    }
}
