<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Field\FieldList;

class FakeContactHelper
{
    public function __construct(
        private FieldList $fieldList,
    ) {
    }

    /**
     * @return array<int|string, int|string|array<int|string, mixed>|null>
     */
    public function prepareFakeContactWithPrimaryCompany(): array
    {
        $contact = $this->prepareFakeEntity('lead');

        $company = $this->prepareFakeEntity('company');

        $company['is_primary'] = 1;

        $contact['companies'][] = $company;

        return $contact;
    }

    /**
     * @return array<int|string, int|string|array<int|string, mixed>|null>
     */
    private function prepareFakeEntity(string $object): array
    {
        $fields = $this->fieldList->getFieldList(false, false, [
            'isPublished' => true,
            'object'      => $object,
        ]);

        array_walk($fields, function (&$field): void {
            $field = "[$field]";
        });

        $fields['id'] = 0;

        return $fields;
    }
}
