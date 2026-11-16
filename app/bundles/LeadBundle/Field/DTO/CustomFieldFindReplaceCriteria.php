<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\DTO;

final readonly class CustomFieldFindReplaceCriteria
{
    public function __construct(
        public string $object,
        public string $fieldAlias,
        public mixed $findValue,
        public mixed $replaceValue,
    ) {
    }
}
