<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Api;

/** Validates the selected public OpenAPI request schemas before a billable request. */
final class Validator
{
    /** @param array<string, mixed> $schema */
    public static function check(mixed $value, array $schema, string $path, int $depth = 0): void
    {
        if ($depth > 20) {
            throw new \InvalidArgumentException('Input nesting is too deep.');
        }
        if (null === $value && ($schema['nullable'] ?? false)) {
            return;
        }
        $type = $schema['type'] ?? null;
        $valid = match ($type) {
            'object' => is_array($value) && ([] === $value || !array_is_list($value)),
            'array' => is_array($value) && array_is_list($value),
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value) || ('int64' === ($schema['format'] ?? null) && is_string($value) && 1 === preg_match('/^[0-9]{1,19}$/D', $value) && (strlen($value) < 19 || strcmp($value, '9223372036854775807') <= 0)),
            'number' => (is_int($value) || is_float($value)) && is_finite((float) $value),
            default => true,
        };
        if (!$valid || (isset($schema['enum']) && !in_array($value, $schema['enum'], true))) {
            throw new \InvalidArgumentException('Invalid value for '.$path.'.');
        }
        if (is_string($value) && (strlen($value) > min(10000, $schema['maxLength'] ?? 10000) || strlen($value) < ($schema['minLength'] ?? 0))) {
            throw new \InvalidArgumentException('Invalid length for '.$path.'.');
        }
        if (is_string($value) && 'uuid' === ($schema['format'] ?? null) && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $value)) {
            throw new \InvalidArgumentException('Enter a valid UUID for '.$path.'.');
        }
        if (is_string($value) && 'uri' === ($schema['format'] ?? null) && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Enter an absolute URL for '.$path.'.');
        }
        if ((is_int($value) || is_float($value)) && ($value < ($schema['minimum'] ?? -INF) || $value > ($schema['maximum'] ?? INF))) {
            throw new \InvalidArgumentException('Value outside the allowed range for '.$path.'.');
        }
        if ('object' === $type) {
            foreach ($schema['required'] ?? [] as $required) {
                if (!array_key_exists($required, $value)) {
                    throw new \InvalidArgumentException('Missing '.$path.'.'.$required.'.');
                }
            }
            foreach ($value as $key => $item) {
                if (!isset($schema['properties'][$key])) {
                    throw new \InvalidArgumentException('Unknown input '.$path.'.'.$key.'.');
                }
                self::check($item, $schema['properties'][$key], $path.'.'.$key, $depth + 1);
            }
        }
        if ('array' === $type) {
            if (count($value) < ($schema['minItems'] ?? 0) || count($value) > min(2500, $schema['maxItems'] ?? 2500)) {
                throw new \InvalidArgumentException('Invalid item count for '.$path.'.');
            }
            foreach ($value as $index => $item) {
                self::check($item, $schema['items'] ?? [], $path.'.'.$index, $depth + 1);
            }
        }
    }
}
