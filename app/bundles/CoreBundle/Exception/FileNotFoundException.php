<?php

namespace Mautic\CoreBundle\Exception;

class FileNotFoundException extends \Exception
{
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        $message = $message ?? 'File not found.';
        $code = $code ?? 0;
        parent::__construct($message, $code, $previous);
    }
}
