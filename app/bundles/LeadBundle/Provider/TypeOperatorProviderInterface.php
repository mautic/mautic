<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use Symfony\Component\Form\FormInterface;

interface TypeOperatorProviderInterface
{
    public function getOperatorsIncluding(array $operators): array;

    public function getOperatorsExcluding(array $operators): array;

    /**
     * @throws OperatorsNotFoundException
     */
    public function getOperatorsForFieldType(string $fieldType): array;

    public function getAllTypeOperators(): array;

    /**
     * Allows subscribers to adjust the filter form so new fields can be added.
     */
    public function adjustFilterPropertiesType(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails): FormInterface;
}
