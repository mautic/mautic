<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Event;

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\EventDispatcher\Event;

final class FormFieldEvent extends Event
{
    /**
     * @var Field
     */
    private $entity;

    /**
     * @var bool
     */
    private $isNew;

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
