<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle;

final class UserEvents
{
    /**
     * The user.save event is thrown each time a user is saved.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_SAVE   = 'user.save';

    /**
     * The user.delete event is thrown each time a user is deleted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_DELETE   = 'user.delete';

    /**
     * The role.save event is thrown each time a role is saved.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_SAVE   = 'role.save';

    /**
     * The role.delete event is thrown each time a role is deleted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_DELETE   = 'role.delete';
}