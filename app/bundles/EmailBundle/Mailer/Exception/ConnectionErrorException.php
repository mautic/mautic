<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Exception;

class ConnectionErrorException extends \Exception
{
    public function __construct(string $message = 'Unable to connect.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
