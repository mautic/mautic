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

/**
 * Class EmailEvent.
 */
class EmailEvent extends CommonEvent
{
    /**
     * @param Email $email
     * @param bool  $isNew
     */
    public function __construct(Email &$email, $isNew = false)
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
     *
     * @param Email $email
     */
    public function setEmail(Email $email)
    {
        $this->entity = $email;
    }
}
