<?php

namespace Mautic\CoreBundle\Exception;

class DatabaseConnectionException extends \Exception
{
    public function __construct($message = 'Unable to connect to the database.', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
