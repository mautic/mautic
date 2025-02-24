<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Form\Type;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<array<string|int|float|array<string|int|float>>|string|int|float, array<string|int|float|array<string|int|float>>|string|int|float>
 */
class EscapeTransformer implements DataTransformerInterface
{
    /**
     * @var string[]
     */
    private array $allowedParameters;

    public function __construct(array $allowedParameters)
    {
        $this->allowedParameters = array_filter($allowedParameters);
    }

    /**
     * @param array<string|int|float|array<string|int|float>>|string|int|float $value
     *
     * @return array<string|int|float|array<string|int|float>>|string|int|float
     */
    public function transform($value)
    {
        if (is_array($value)) {
            return array_map(fn ($value) => $this->unescape($value), $value);
        }

        return $this->unescape($value);
    }

    /**
     * @param array<string|int|float|array<string|int|float>>|string|int|float $value
     *
     * @return array<string|int|float|array<string|int|float>>|string|int|float
     */
    public function reverseTransform($value)
    {
        if (is_array($value)) {
            return array_map(fn ($value) => $this->escape($value), $value);
        }

        return $this->escape($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function unescape($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace('%%', '%', $value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function escape($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $escaped = str_replace('%', '%%', $value);

        return $this->allowParameters($escaped);
    }

    private function allowParameters(string $escaped): string
    {
        if (!$this->allowedParameters) {
            return $escaped;
        }

        $search  = array_map(fn (string $value): string => "%%{$value}%%", $this->allowedParameters);
        $replace = array_map(fn (string $value): string => "%{$value}%", $this->allowedParameters);

        return str_ireplace($search, $replace, $escaped);
    }
}
