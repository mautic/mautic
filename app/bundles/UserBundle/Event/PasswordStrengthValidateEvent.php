<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PasswordStrengthValidateEvent extends Event
{
    public function __construct(
        public bool $isValid,
        public string $password,
    ) {
    }
}
