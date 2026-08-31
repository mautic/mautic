<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

final class DatabaseConnectionException extends \Exception
{
    public function __construct(string $message = 'Unable to connect to the database.', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
