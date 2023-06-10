<?php

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class EmailReplyEvent.
 */
class EmailReplyEvent extends Event
{
    /**
     * @var Email
     */
    private $email;

    public function __construct(private Stat $stat)
    {
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
