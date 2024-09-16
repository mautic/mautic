<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Form\Type;

use Symfony\Component\Form\DataTransformerInterface;

class EscapeTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    private $allowedParameters;

    public function __construct(array $allowedParameters)
    {
        $this->allowedParameters = array_filter($allowedParameters);
    }

    public function transform($value)
    {
        if (is_array($value)) {
            return array_map(function ($value) {
                return $this->unescape($value);
            }, $value);
        }

        return $this->unescape($value);
    }

    public function reverseTransform($value)
    {
        if (is_array($value)) {
            return array_map(function ($value) {
                return $this->escape($value);
            }, $value);
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

        $search  = array_map(function (string $value) {
            return "%%{$value}%%";
        }, $this->allowedParameters);
        $replace = array_map(function (string $value) {
            return "%{$value}%";
        }, $this->allowedParameters);

        return str_ireplace($search, $replace, $escaped);
    }
}
