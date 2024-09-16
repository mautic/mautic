<?php

namespace Mautic\CoreBundle\Exception;

class FileNotFoundException extends \Exception
{
    public function __construct($message = 'File not found.', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
