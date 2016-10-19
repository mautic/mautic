<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle;

/**
 * Class UserEvents.
 *
 * Events available for UserBundle
 */
final class UserEvents
{
    /**
     * The mautic.user_pre_save event is dispatched right before a user is persisted.
     *
     * The event listener receives a Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_PRE_SAVE = 'mautic.user_pre_save';

    /**
     * The mautic.user_post_save event is dispatched right after a user is persisted.
     *
     * The event listener receives a Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_POST_SAVE = 'mautic.user_post_save';

    /**
     * The mautic.user_pre_delete event is dispatched prior to when a user is deleted.
     *
     * The event listener receives a Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_PRE_DELETE = 'mautic.user_pre_delete';

    /**
     * The mautic.user_post_delete event is dispatched after a user is deleted.
     *
     * The event listener receives a Mautic\UserBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_POST_DELETE = 'mautic.user_post_delete';

    /**
     * The mautic.role_pre_save event is dispatched right before a role is persisted.
     *
     * The event listener receives a Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_PRE_SAVE = 'mautic.role_pre_save';

    /**
     * The mautic.role_post_save event is dispatched right after a role is persisted.
     *
     * The event listener receives a Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_POST_SAVE = 'mautic.role_post_save';

    /**
     * The mautic.role_pre_delete event is dispatched prior a role being deleted.
     *
     * The event listener receives a Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_PRE_DELETE = 'mautic.role_pre_delete';

    /**
     * The mautic.role_post_delete event is dispatched after a role is deleted.
     *
     * The event listener receives a Mautic\UserBundle\Event\RoleEvent instance.
     *
     * @var string
     */
    const ROLE_POST_DELETE = 'mautic.role_post_delete';

    /**
     * The mautic.user_logout event is dispatched during the logout routine giving a chance to carry out tasks before
     * the session is lost.
     *
     * The event listener receives a Mautic\UserBundle\Event\LogoutEvent instance.
     *
     * @var string
     */
    const USER_LOGOUT = 'mautic.user_logout';

    /**
     * The mautic.user_login event is dispatched right after a user logs in.
     *
     * The event listener receives a Mautic\UserBundle\Event\LoginEvent instance.
     *
     * @var string
     */
    const USER_LOGIN = 'mautic.user_login';

    /**
     * The mautic.user_form_authentication event is dispatched when a user logs in so that listeners can authenticate a user, i.e. via a 3rd party service.
     *
     * The event listener receives a Mautic\UserBundle\Event\AuthenticationEvent instance.
     *
     * @var string
     */
    const USER_FORM_AUTHENTICATION = 'mautic.user_form_authentication';

    /**
     * The mautic.user_pre_authentication event is dispatched when a user browses a page under /s/ except for /login. This allows support for
     * 3rd party authentication providers outside the login form.
     *
     * The event listener receives a Mautic\UserBundle\Event\AuthenticationEvent instance.
     *
     * @var string
     */
    const USER_PRE_AUTHENTICATION = 'mautic.user_pre_authentication';

    /**
     * The mautic.user_authentication_content event is dispatched to collect HTML from plugins to be injected into the UI to assist with
     * authentication.
     *
     * The event listener receives a Mautic\UserBundle\Event\AuthenticationContentEvent instance.
     *
     * @var string
     */
    const USER_AUTHENTICATION_CONTENT = 'mautic.user_authentication_content';

    /**
     * The mautic.user_status_change event is dispatched when a user's online status is changed.
     *
     * The event listener receives a Mautic\UserBundle\Event\StatusChangeEvent instance.
     *
     * @var string
     */
    const STATUS_CHANGE = 'mautic.user_status_change';
}
