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

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Exception\OperatorsNotFoundException;

interface TypeOperatorProviderInterface
{
    public function getOperatorsIncluding(array $operators): array;

    public function getOperatorsExcluding(array $operators): array;

    /**
     * @throws OperatorsNotFoundException
     */
    public function getOperatorsForFieldType(string $fieldType): array;

    public function getAllTypeOperators(): array;
}
