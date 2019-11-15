<?php

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
    /**
     * @var array
     */
    private $choicesForTypes = [];

    /**
     * @var array
     */
    private $choicesForAliases = [];

    /**
     * @param string $fieldType
     * @param array  $choices
     */
    public function setChoicesForFieldType(string $fieldType, array $choices)
    {
        $this->choicesForTypes[$fieldType] = $choices;
    }

    /**
     * @param string $fieldType
     * @param array  $choices
     */
    public function setChoicesForFieldAlias(string $fieldAlias, array $choices)
    {
        $this->choicesForAliases[$fieldAlias] = $choices;
    }

    /**
     * @return array
     */
    public function getChoicesForAllListFieldTypes()
    {
        return $this->choicesForTypes;
    }

    /**
     * @return array
     */
    public function getChoicesForAllListFieldAliases()
    {
        return $this->choicesForAliases;
    }
}
