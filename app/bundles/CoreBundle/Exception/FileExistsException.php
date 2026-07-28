<?php

namespace Mautic\CoreBundle\Exception;

final class FileExistsException extends \Exception
{
    public function __construct($message = 'File exists.', $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
