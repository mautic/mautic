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
    private $choices = [];

    /**
     *
     * @param string $fieldType
     * @param array $choices
     */
    public function setChoicesForFieldType($fieldType, array $choices)
    {
        $this->choices[$fieldType] = $choices;
    }

    /**
     * @return array
     */
    public function getChoicesForAllListFieldTypes()
    {
        return $this->choices;
    }
}
