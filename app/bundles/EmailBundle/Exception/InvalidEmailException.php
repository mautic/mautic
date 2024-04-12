<?php

namespace Mautic\EmailBundle\Exception;

use Mautic\CoreBundle\Exception\InvalidValueException;

class InvalidEmailException extends InvalidValueException
{
    public function __construct(
        protected string $emailAddress,
        string $message = '',
        int $code = 0,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
}
