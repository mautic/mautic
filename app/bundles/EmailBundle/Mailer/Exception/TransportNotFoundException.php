<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Exception;

class TransportNotFoundException extends \LogicException
{
    public static function fromName(string $name): self
    {
        return new self(sprintf('Transport Extension "%s" not found.', $name));
    }
}
