<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Event;

use Mautic\FormBundle\Entity\Field;
use Symfony\Contracts\EventDispatcher\Event;

final class FormFieldEvent extends Event
{
    private \Mautic\FormBundle\Entity\Field $entity;

    private bool $isNew;

    public function __construct(Field $field, bool $isNew = false)
    {
        $this->entity = $field;
        $this->isNew  = $isNew;
    }

    public function getField(): Field
    {
        return $this->entity;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setField(Field $field): void
    {
        $this->entity = $field;
    }
}
