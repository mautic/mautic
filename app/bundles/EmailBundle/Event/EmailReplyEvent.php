<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

class EmailReplyEvent extends Event
{
    private ?Email $email;

    public function __construct(
        private Stat $stat,
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
