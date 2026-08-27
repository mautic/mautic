<?php

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Component\HttpFoundation\Request;

final class EmailOpenEvent extends CommonEvent
{
    private readonly ?Email $email;

    /**
     * @param Request $request
     */
    public function __construct(
        Stat $stat,
        private $request,
        private readonly bool $firstTime = false,
    ) {
        $this->entity    = $stat;
        $this->email     = $stat->getEmail();
    }

    /**
     * Returns the Email entity.
     */
    public function getEmail(): ?Email
    {
        return $this->email;
    }

    /**
     * Get email request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->entity;
    }

    /**
     * Returns if this is first time the email is read.
     */
    public function isFirstTime(): bool
    {
        return $this->firstTime;
    }
}
