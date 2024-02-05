<?php

namespace Mautic\CoreBundle\Exception;

class SchemaException extends \Exception
{
    public function __construct($message = 'Could not perform schema change.', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
