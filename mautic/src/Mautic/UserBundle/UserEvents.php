<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle;

/**
 * Class UserEvents
 * Events available for UserBundle
 *
 * @package Mautic\UserBundle
 */
final class UserEvents
{
    /**
     * The mautic.user_pre_save event is thrown right before a user is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_PRE_SAVE   = 'mautic.user_pre_save';

    /**
     * The mautic.user_post_save event is thrown right after a user is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_POST_SAVE   = 'mautic.user_post_save';

    /**
     * The mautic.user_delete event is thrown each time a user is deleted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_DELETE   = 'mautic.user_delete';

    /**
     * The mautic.role_pre_save event is thrown right before a role is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_PRE_SAVE   = 'mautic.role_pre_save';

    /**
     * The mautic.role_post_save event is thrown right after a role is persisted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_POST_SAVE   = 'mautic.role_post_save';

    /**
     * The mautic.role_delete event is thrown each time a role is deleted.
     *
     * The event listener receives a
     * Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_DELETE   = 'mautic.role_delete';
}