<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Event;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Crate\FieldCrate;
use Symfony\Contracts\EventDispatcher\Event;

final class FieldCollectEvent extends Event
{
    private FieldCollection $fields;

    public function __construct(
        private string $object
    ) {
        $this->fields = new FieldCollection();
    }

    public function getObject(): string
    {
        return $this->object;
    }

    public function appendField(FieldCrate $field): void
    {
        $this->fields->append($field);
    }

    public function getFields(): FieldCollection
    {
        return $this->fields;
    }
}
