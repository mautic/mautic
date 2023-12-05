<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

class EmailReplyEvent extends Event
{
    private ?\Mautic\EmailBundle\Entity\Email $email;

    private \Mautic\EmailBundle\Entity\Stat $stat;

    public function __construct(Stat $stat)
    {
        $this->stat  = $stat;
        $this->email = $stat->getEmail();
    }

    /**
     * Returns the Email entity.
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }
}
