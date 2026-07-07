<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

/**
 * Reusable pinned search-scope entries for list filter dropdowns.
 */
final class SearchScopePresets
{
    /**
     * @return array{key: string, label: string, default: true}
     */
    public static function standard(): array
    {
        return ['key' => '', 'label' => 'mautic.core.search.scope.standard', 'default' => true];
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function command(string $translationKey, ?string $labelKey = null): array
    {
        return ['key' => $translationKey, 'label' => $labelKey ?? $translationKey];
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function field(string $fieldKey, string $labelKey): array
    {
        return ['key' => $fieldKey, 'label' => $labelKey];
    }

    public static function category(): array
    {
        return self::command('mautic.core.searchcommand.category');
    }

    public static function ids(): array
    {
        return self::command('mautic.core.searchcommand.ids');
    }

    public static function name(): array
    {
        return self::command('mautic.core.searchcommand.name');
    }

    public static function lang(): array
    {
        return self::command('mautic.core.searchcommand.lang');
    }

    public static function email(): array
    {
        return self::command('mautic.core.searchcommand.email');
    }

    public static function isPublished(): array
    {
        return self::command('mautic.core.searchcommand.ispublished');
    }

    public static function isUnpublished(): array
    {
        return self::command('mautic.core.searchcommand.isunpublished');
    }

    public static function isUncategorized(): array
    {
        return self::command('mautic.core.searchcommand.isuncategorized');
    }

    public static function isMine(): array
    {
        return self::command('mautic.core.searchcommand.ismine');
    }

    public static function project(): array
    {
        return self::command('mautic.project.searchcommand.name');
    }

    /**
     * @param list<array{key: string, label: string, default?: bool}> ...$groups
     *
     * @return list<array{key: string, label: string, default?: bool}>
     */
    public static function merge(array ...$groups): array
    {
        return array_merge(...$groups);
    }
}
