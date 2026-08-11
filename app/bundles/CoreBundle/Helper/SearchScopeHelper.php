<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

/**
 * Splits and composes list-search strings for the filter-scope dropdown UI.
 *
 * Argument scopes keep only the user term in the visible input and compose
 * "firstname:John" for the request. Flag scopes (is:published, …) may also
 * carry optional free-text after the command ("is:unpublished Newsletter").
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

            // Flag-style commands (is:published, …) combine with free-text via a space.
            if (str_contains($command, ':')) {
                $spaced = $command.' ';
                if (str_starts_with($search, $spaced)) {
                    return [
                        'command' => $command,
                        'value'   => substr($search, strlen($spaced)),
                    ];
                }

                continue;
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

        // Flag-style commands are complete alone; optional free-text searches the item name.
        if (str_contains($command, ':')) {
            return '' === $value ? $command : $command.' '.$value;
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
     * Number of non-breaking spaces used to visually indent custom-field options.
     */
    private const INDENT = "\u{00A0}\u{00A0}\u{00A0}\u{00A0}";

    /**
     * Format a scope label for display in the dropdown (e.g. "is:published" -> "Is:Published").
     *
     * @param bool $indent Visually indent the label (e.g. to set it apart as a custom field)
     */
    public static function formatLabel(string $label, bool $indent = false): string
    {
        if (!str_contains($label, ':')) {
            $formatted = ucfirst($label);
        } else {
            $parts     = explode(':', $label);
            $formatted = implode(':', array_map(
                ucfirst(...),
                $parts
            ));
        }

        return $indent ? self::INDENT.$formatted : $formatted;
    }
}
