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

/**
 * Class EmailReplyEvent.
 */
class EmailReplyEvent extends CommonEvent
{
    /**
     * @var Email
     */
    private $email;

    /**
     * @param Stat $stat
     */
    public function __construct(Stat $stat)
    {
        $this->entity  = $stat;
        $this->email   = $stat->getEmail();
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
        return $this->entity;
    }

}
