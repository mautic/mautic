<?php

namespace Mautic\CoreBundle\Exception;

class InvalidDecodedStringException extends \InvalidArgumentException
{
    public function __construct(string $string = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('The string %s is not a serialized array', $string), $code, $previous);
    }
}
