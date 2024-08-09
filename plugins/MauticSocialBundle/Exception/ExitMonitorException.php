<?php

namespace MauticPlugin\MauticSocialBundle\Exception;

class ExitMonitorException extends \Exception
{
    public function __construct($message = 'Exit monitor requested', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
