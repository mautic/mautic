<?php

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Field;

class ValidationEvent extends CommonEvent
{
    /**
     * @var bool
     */
    private $valid = true;

    /**
     * @var string
     */
    private $invalidReason = '';

    /**
     * @param mixed $value
     */
    public function __construct(
        private Field $field,
        private $value
    )
    {
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
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Get the reason this field was invalidated.
     *
     * @return string
     */
    public function getInvalidReason()
    {
        return $this->invalidReason;
    }
}
