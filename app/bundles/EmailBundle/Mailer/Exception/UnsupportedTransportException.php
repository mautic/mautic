<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Exception;

class UnsupportedTransportException extends \LogicException
{
    public static function fromName(string $name, string $feature): self
    {
        return new self(sprintf('Transport "%s" does not support: "%s".', $name, $feature));
    }
}
