<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

class EmailReplyEvent extends Event
{
    private readonly ?Email $email;

    public function __construct(
        private readonly Stat $stat,
    ) {
        $this->email = $stat->getEmail();
    }

    /**
     * Returns the Email entity.
     */
    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }
}
