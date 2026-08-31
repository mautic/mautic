<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

final class BadConfigurationException extends \Exception
{
    public function __construct(string $message = 'Configuration is bad.', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
