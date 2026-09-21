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

    /**
     * @return array{key: string, label: string}
     */
    public static function category(): array
    {
        return self::command('mautic.core.searchcommand.category');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function ids(): array
    {
        return self::command('mautic.core.searchcommand.ids');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function name(): array
    {
        return self::command('mautic.core.searchcommand.name');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function lang(): array
    {
        return self::command('mautic.core.searchcommand.lang');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function email(): array
    {
        return self::command('mautic.core.searchcommand.email');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function isPublished(): array
    {
        return self::command('mautic.core.searchcommand.ispublished');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function isUnpublished(): array
    {
        return self::command('mautic.core.searchcommand.isunpublished');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function isUncategorized(): array
    {
        return self::command('mautic.core.searchcommand.isuncategorized');
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function isMine(): array
    {
        return self::command('mautic.core.searchcommand.ismine');
    }

    /**
     * @return array{key: string, label: string}
     */
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
