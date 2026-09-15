<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Exception;

final class ExitMonitorException extends \Exception
{
    public function __construct(string $message = 'Exit monitor requested', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
