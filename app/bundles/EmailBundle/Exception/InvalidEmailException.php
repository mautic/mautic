<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Exception;

use Mautic\CoreBundle\Exception\InvalidValueException;

final class InvalidEmailException extends InvalidValueException
{
    public function __construct(
        private readonly string $emailAddress,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
}
