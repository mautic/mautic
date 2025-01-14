<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Exception\OperatorsNotFoundException;

interface TypeOperatorProviderInterface
{
    /**
     * @param mixed[] $operators
     *
     * @return mixed[]
     */
    public function getOperatorsIncluding(array $operators): array;

    /**
     * @param mixed[] $operators
     *
     * @return mixed[]
     */
    public function getOperatorsExcluding(array $operators): array;

    /**
     * @return mixed[]
     *
     * @throws OperatorsNotFoundException
     */
    public function getOperatorsForFieldType(string $fieldType): array;

    /**
     * @return mixed[]
     */
    public function getAllTypeOperators(): array;
}
