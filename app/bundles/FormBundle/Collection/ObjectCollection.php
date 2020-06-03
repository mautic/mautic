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

use Mautic\FormBundle\Crate\ObjectCrate;

final class ObjectCollection extends \ArrayIterator
{
    public function toChoices(): array
    {
        $choices = [];

        /** @var ObjectCrate $object */
        foreach ($this as $object) {
            $choices[$object->getName()] = $object->getKey();
        }

        return $choices;
    }
}
