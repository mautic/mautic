<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

/**
 * Class ValidationEvent.
 */
class ValidationEvent extends CommonEvent
{
    /**
     * @var Field
     */
    private $field;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var
     */
    private $valid = true;

    /**
     * @var string
     */
    private $invalidReason = '';

    /**
     * @param Form $form
     * @param bool $isNew
     */
    public function __construct(Field $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
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

    /**
     * @param $reason
     */
    public function failedValidation($reason)
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
