<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EmailOpenEvent.
 */
class EmailOpenEvent extends CommonEvent
{
    /**
     * @var
     */
    protected $request;

    /**
     * @var
     */
    protected $code;

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var array
     */
    protected $clickthroughData = [];

    /**
     * @var bool
     */
    protected $unique;


    /**
     * PageHitEvent constructor.
     *
     * @param Hit   $hit
     * @param       $request
     * @param       $code
     * @param array $clickthrough
     * @param bool  $isUnique
     */
    public function __construct(Hit $hit, $request, $code, $clickthrough = [], $isUnique = false)
    {
        $this->entity           = $hit;
        $this->page             = $hit->getPage();
        $this->request          = $request;
        $this->code             = $code;
        $this->clickthroughData = $clickthrough;
        $this->unique           = $isUnique;
        $this->email     = $hit->getEmail();
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
