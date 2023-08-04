<?php

namespace Mautic\EmailBundle\Exception;

use Mautic\CoreBundle\Exception\InvalidValueException;

class InvalidEmailException extends InvalidValueException
{
    protected string $emailAddress;

    public function __construct(string $emailAddress, string $message = '', int $code = 0, \Throwable|null $previous = null)
    {
        $this->emailAddress = $emailAddress;

        parent::__construct($message, $code, $previous);
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
}
