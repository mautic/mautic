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
     * The user.pre_save event is thrown right before a user is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_PRE_SAVE   = 'user.pre_save';

    /**
     * The user.post_save event is thrown right after a user is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_POST_SAVE   = 'user.post_save';

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
     * The role.pre_save event is thrown right before a role is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_PRE_SAVE   = 'role.pre_save';

    /**
     * The role.post_save event is thrown right after a role is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_POST_SAVE   = 'role.post_save';

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