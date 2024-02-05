<?php

namespace Mautic\CoreBundle\Exception;

class BadConfigurationException extends \Exception
{
    public function __construct($message = 'Configuration is bad.', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
