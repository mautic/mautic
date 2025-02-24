<?php

namespace Mautic\FormBundle\Exception;

class ValidationException extends \Exception
{
    /**
     * @var mixed[]
     */
    private array $violations = [];

    public function __construct($message = 'Validation failed', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param mixed[] $violations
     */
    public function setViolations(array $violations): self
    {
        $this->violations = $violations;

        return $this;
    }
}
