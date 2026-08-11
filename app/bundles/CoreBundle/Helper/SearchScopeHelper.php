<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

/**
 * Splits and composes list-search strings for the filter-scope dropdown UI.
 *
 * Argument scopes keep only the user term in the visible input and compose
 * "firstname:John" for the request. Extra commands typed in the input
 * (e.g. "pepa ids:5") are appended: "name:pepa ids:5". Flag scopes
 * (is:published, …) may also carry optional free-text / extra commands
 * after the command ("is:unpublished Newsletter ids:5").
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

        // Flag-style commands are complete alone; optional free-text / extra commands follow.
        if (str_contains($command, ':')) {
            return '' === $value ? $command : $command.' '.$value;
        }

        if ('' === $value) {
            return '';
        }

        $parts = self::splitTermAndExtraCommands($value);
        if ('' === $parts['term']) {
            // Input is only extra commands (e.g. "ids:5") — do not wrap as name:ids:5.
            return $parts['extra'];
        }

        $composed = $command.':'.$parts['term'];
        if ('' !== $parts['extra']) {
            $composed .= ' '.$parts['extra'];
        }

        return $composed;
    }

    /**
     * Split a visible search-input value into the scope term and trailing commands.
     *
     * Example: "pepa ids:5 category:news" → term "pepa", extra "ids:5 category:news".
     *
     * @return array{term: string, extra: string}
     */
    public static function splitTermAndExtraCommands(string $value): array
    {
        $value = trim($value);
        if ('' === $value) {
            return ['term' => '', 'extra' => ''];
        }

        if (!str_contains($value, ':')) {
            return ['term' => $value, 'extra' => ''];
        }

        $tokens      = self::tokenizeRespectingQuotes($value);
        $termTokens  = [];
        $extraTokens = [];
        $inExtra     = false;

        foreach ($tokens as $token) {
            if (!$inExtra && self::tokenLooksLikeCommand($token)) {
                $inExtra = true;
            }

            if ($inExtra) {
                $extraTokens[] = $token;
            } else {
                $termTokens[] = $token;
            }
        }

        return [
            'term'  => implode(' ', $termTokens),
            'extra' => implode(' ', $extraTokens),
        ];
    }

    /**
     * @return list<string>
     */
    private static function tokenizeRespectingQuotes(string $value): array
    {
        preg_match_all('/"[^"]*"|\S+/', $value, $matches);

        return $matches[0] ?? [];
    }

    private static function tokenLooksLikeCommand(string $token): bool
    {
        // Strip surrounding quotes for the check; commands are never quoted as a whole.
        $token = trim($token, '"');

        return 1 === preg_match('/^!?[\p{L}\d_.-]+:/u', $token);
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
