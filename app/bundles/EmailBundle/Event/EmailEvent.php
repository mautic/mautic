<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;

class EmailEvent extends CommonEvent
{
    public function __construct(Email &$email, bool $isNew = false)
    {
        $this->entity = &$email;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Email entity.
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->entity;
    }

    /**
     * Sets the Email entity.
     */
    public function setEmail(Email $email): void
    {
        $this->entity = $email;
    }
}
