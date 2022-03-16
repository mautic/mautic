<?php

namespace Mautic\FormBundle\Exception;

/**
 * Class ValidationException.
 */
class ValidationException extends \Exception
{
    private $violations = [];

    public function __construct($message = 'Validation failed', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param array $violations
     *
     * @return ValidationException
     */
    public function setViolations($violations)
    {
        $this->violations = $violations;

        return $this;
    }
}
