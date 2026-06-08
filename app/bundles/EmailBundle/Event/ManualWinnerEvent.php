<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Symfony\Contracts\EventDispatcher\Event;

final class ManualWinnerEvent extends Event
{
    public function __construct(private Email $email)
    {
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
