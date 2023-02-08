<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

interface AlreadyMappedFieldCollectorInterface
{
    /**
     * @param string $formId can be a string hash for new forms
     *
     * @return mixed[]
     */
    public function getFields(string $formId, string $object): array;

    public function addField(string $formId, string $object, string $fieldKey): void;

    public function removeField(string $formId, string $object, string $fieldKey): void;

    /**
     * Removes all mapped fields for the specified form.
     */
    public function removeAllForForm(string $formId): void;
}
