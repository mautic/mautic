<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

interface TypeOperatorProviderWithFieldTypeInterface
{
    /**
     * @param mixed[] $operators
     *
     * @return mixed[]
     */
    public function getOperatorsIncludingFieldType(array $operators, string $fieldType): array;
}
