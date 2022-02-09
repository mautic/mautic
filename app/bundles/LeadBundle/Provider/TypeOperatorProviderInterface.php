<?php

declare(strict_types=1);

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
