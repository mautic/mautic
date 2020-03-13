<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects choices for different list field types.
 */
class ListFieldChoicesEvent extends Event
{
    private $choicesForTypes = [];

    private $choicesForAliases = [];

    public function setChoicesForFieldType(string $fieldType, array $choices): void
    {
        $this->choicesForTypes[$fieldType] = $choices;
    }

    public function setChoicesForFieldAlias(string $fieldAlias, array $choices): void
    {
        $this->choicesForAliases[$fieldAlias] = $choices;
    }

    public function getChoicesForAllListFieldTypes(): array
    {
        return $this->choicesForTypes;
    }

    public function getChoicesForAllListFieldAliases(): array
    {
        return $this->choicesForAliases;
    }
}
