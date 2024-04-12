<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collection;

use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Exception\FieldNotFoundException;

/**
 * @extends \ArrayIterator<int,FieldCrate>
 */
final class FieldCollection extends \ArrayIterator
{
    /**
     * @return array<string,string>
     */
    public function toChoices(): array
    {
        $choices = [];

        /** @var FieldCrate $field */
        foreach ($this as $field) {
            $choices[$field->getName()] = $field->getKey();
        }

        return $choices;
    }

    /**
     * @return FieldCrate|null
     */
    public function getFieldByKey(string $key, bool $throwExceptionIfNotFound = true)
    {
        /** @var FieldCrate $field */
        foreach ($this as $field) {
            if ($key === $field->getKey()) {
                return $field;
            }
        }

        if ($throwExceptionIfNotFound) {
            throw new FieldNotFoundException("Field with key {$key} was not found.");
        }

        return null;
    }

    /**
     * @param string[] $keys
     */
    public function removeFieldsWithKeys(array $keys, string $keyToKeep = null): FieldCollection
    {
        return new self(
            array_filter(
                $this->getArrayCopy(),
                fn (FieldCrate $field): bool => ($keyToKeep && $field->getKey() === $keyToKeep) || !in_array($field->getKey(), $keys, true)
            )
        );
    }
}
