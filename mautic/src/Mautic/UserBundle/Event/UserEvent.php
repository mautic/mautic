<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\UserBundle\Entity\User;

/**
 * Class UserEvent
 *
 * @package Mautic\UserBundle\Event
 */
class UserEvent extends CommonEvent
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
    public function __construct(User &$user, $isNew = false)
    {
        $this->user  =& $user;
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
     * Sets the User entity
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Returns if a saved user is new or not
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * Determines changes to original entity
     *
     * @return mixed
     */
    public function getChanges() {
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($this->user);
        return $changeset;
    }
}
