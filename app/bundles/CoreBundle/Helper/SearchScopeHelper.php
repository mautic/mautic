<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

/**
 * Splits and composes list-search strings for the filter-scope dropdown UI.
 *
 * When a scope is active the visible input holds only the user term; the full
 * search command (e.g. "firstname:John") is composed before the request is sent.
 */
final class SearchScopeHelper
{
    /**
     * @param list<string> $scopeCommands Scope command prefixes (empty string = standard/free-text)
     *
     * @return array{command: string, value: string}
     */
    public static function parse(string $search, array $scopeCommands): array
    {
        $search = trim($search);
        if ('' === $search) {
            return ['command' => '', 'value' => ''];
        }

        $commands = self::sortCommandsLongestFirst($scopeCommands);

        foreach ($commands as $command) {
            if ('' === $command) {
                continue;
            }

            if ($command === $search) {
                return ['command' => $command, 'value' => ''];
            }

            $prefix = $command.':';
            if (str_starts_with($search, $prefix)) {
                return [
                    'command' => $command,
                    'value'   => substr($search, strlen($prefix)),
                ];
            }
        }

        return ['command' => '', 'value' => $search];
    }

    public static function compose(string $command, string $value): string
    {
        $value = trim($value);

        if ('' === $command) {
            return $value;
        }

        if ('' === $value && str_contains($command, ':')) {
            return $command;
        }

        if ('' === $value) {
            return '';
        }

        return $command.':'.$value;
    }

    /**
     * @param list<string> $scopeCommands
     *
     * @return list<string>
     */
    private static function sortCommandsLongestFirst(array $scopeCommands): array
    {
        $commands = array_values(array_unique($scopeCommands));
        usort(
            $commands,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
        );

        return $commands;
    }

    /**
     * Format a scope label for display in the dropdown (e.g. "is:published" -> "Is:Published").
     */
    public static function formatLabel(string $label): string
    {
        if (!str_contains($label, ':')) {
            return ucfirst($label);
        }

        $parts = explode(':', $label);

        return implode(':', array_map(
            static fn (string $part): string => ucfirst($part),
            $parts
        ));
    }
}
