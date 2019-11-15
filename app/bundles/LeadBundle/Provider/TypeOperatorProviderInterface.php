<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\Exception\OperatorsNotFoundException;

interface TypeOperatorProviderInterface
{
    /**
     * @param array $operators
     *
     * @return array
     */
    public function getOperatorsIncluding(array $operators);

    /**
     * @param array $operators
     *
     * @return array
     */
    public function getOperatorsExcluding(array $operators);

    /**
     * @param string $filedType
     * @param string $fieldAlias
     *
     * @return array
     *
     * @throws ChoicesNotFoundException
     */
    public function getChoicesForField(string $fieldType, string $fieldAlias);

    /**
     * @param string $filedType
     *
     * @return array
     *
     * @throws OperatorsNotFoundException
     */
    public function getOperatorsForFieldType($fieldType);

    /**
     * @return array
     */
    public function getAllTypeOperators();

    /**
     * @return array
     */
    public function getAllChoicesForListFieldTypes();
}
