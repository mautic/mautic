<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Enum;

enum PackageType: string
{
    case PLUGIN   = 'mautic-plugin';
    case THEME    = 'mautic-theme';
    case CAMPAIGN = 'mautic-campaign';

    /**
     * @return array<string, string>
     */
    public static function getSearchCommandMap(): array
    {
        return [
            'is:plugin'   => self::PLUGIN->value,
            'is:theme'    => self::THEME->value,
            'is:campaign' => self::CAMPAIGN->value,
        ];
    }

    /**
     * Returns regex pattern for matching type commands in search (e.g., "is:plugin", "is:theme").
     */
    public static function getTypeCommandPattern(): string
    {
        $types = array_map(fn (self $case): string => strtolower($case->name), self::cases());

        return '/is:('.implode('|', $types).')/i';
    }
}
