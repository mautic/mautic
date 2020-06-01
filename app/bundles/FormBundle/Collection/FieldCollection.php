<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Collection;

use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Exception\FieldNotFoundException;

final class FieldCollection extends \ArrayIterator
{
    public function toChoices(): array
    {
        $choices = [];

        /** @var FieldCrate $field */
        foreach ($this as $field) {
            $choices[$field->getName()] = $field->getKey();
        }

        return $choices;
    }

    public function getFieldByKey(string $key): FieldCrate
    {
        /** @var FieldCrate $field */
        foreach ($this as $field) {
            if ($key === $field->getKey()) {
                return $field;
            }
        }

        throw new FieldNotFoundException("Field with key {$key} was not found.");
    }

    public function removeFieldsWithKeys(array $keys, string $keyToKeep = null): FieldCollection
    {
        return new self(
            array_filter(
                $this->getArrayCopy(),
                function (FieldCrate $field) use ($keys, $keyToKeep) {
                    return ($keyToKeep && $field->getKey() === $keyToKeep) || !in_array($field->getKey(), $keys, true);
                }
            )
        );
    }
}
