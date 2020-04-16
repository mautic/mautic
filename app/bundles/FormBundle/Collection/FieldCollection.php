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

final class FieldCollection extends \ArrayIterator
{
    public function getFields(FieldCrate $field): void
    {
        parent::append($field);
    }

    public function toChoices(): array
    {
        $choices = [];

        /** @var FieldCrate $field */
        foreach ($this as $field) {
            $choices[$field->getName()] = $field->getKey();
        }

        return $choices;
    }
}
