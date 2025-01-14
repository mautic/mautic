<?php

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EmailOpenEvent.
 */
class EmailOpenEvent extends CommonEvent
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var bool
     */
    private $firstTime;

    /**
     * @param Email $email
     */
    public function __construct(Stat $stat, $request, $firstTime = false)
    {
        $this->entity    = $stat;
        $this->email     = $stat->getEmail();
        $this->request   = $request;
        $this->firstTime = $firstTime;
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
     *
     * @return bool
     */
    public function isFirstTime()
    {
        return $this->firstTime;
    }
}
