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
     *
     * @return Email
     */
    public function getEmail(): ?\Mautic\EmailBundle\Entity\Email
    {
        return $this->email;
    }

    public function getStat(): \Mautic\EmailBundle\Entity\Stat
    {
        return $this->stat;
    }
}
