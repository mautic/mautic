<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Mautic\UserBundle\Entity\User;

/**
 * Class UserEvent
 *
 * @package Mautic\UserBundle\Event
 */
class UserEvent extends Event
{
    /**
     * @var \Mautic\UserBundle\Entity\User
     */
    protected $user;

    /**
     * @var
     */
    protected $isNew;

    /**
     * @param User $user
     * @param bool $isNew
     */
    public function __construct(User $user, $isNew = false)
    {
        $this->user  = $user;
        $this->isNew = $isNew;
    }

    /**
     * Returns the User entity
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns if a saved user is new or not
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }
}
