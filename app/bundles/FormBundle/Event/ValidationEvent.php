<?php

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Field;

class ValidationEvent extends CommonEvent
{
    private bool $valid = true;

    private string $invalidReason = '';

    /**
     * @param mixed $value
     */
    public function __construct(
        private Field $field,
        private $value
    ) {
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function failedValidation($reason): void
    {
        $this->valid         = false;
        $this->invalidReason = $reason;

        $this->stopPropagation();
    }

    /**
     * Is the field valid.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get the reason this field was invalidated.
     */
    public function getInvalidReason(): string
    {
        return $this->invalidReason;
    }
}
