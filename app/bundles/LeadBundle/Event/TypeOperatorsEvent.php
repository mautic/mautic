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
 * Event that collects operators for different field types.
 */
class TypeOperatorsEvent extends Event
{
    /**
     * @var array
     */
    private $operators = [];

    /**
     * @param string $fieldType
     * @param array  $operators
     */
    public function setOperatorsForFieldType($fieldType, array $operators)
    {
        $this->operators[$fieldType] = $operators;
    }

    /**
     * @return array
     */
    public function getOperatorsForAllFieldTypes()
    {
        return $this->operators;
    }
}
