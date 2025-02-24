<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Crate;

use Mautic\LeadBundle\Helper\FormFieldHelper;

final class FieldCrate
{
    /**
     * @param mixed[] $properties
     */
    public function __construct(
        private string $key,
        private string $name,
        private string $type,
        private array $properties
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function isListType(): bool
    {
        $isListType    = in_array($this->getType(), FormFieldHelper::getListTypes());
        $hasList       = !empty($this->getProperties()['list']);
        $hasOptionList = !empty($this->getProperties()['optionlist']);

        return $isListType || $hasList || $hasOptionList;
    }
}
