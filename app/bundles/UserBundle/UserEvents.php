<?php

declare(strict_types=1);

namespace Mautic\UserBundle;

/**
 * Events available for UserBundle.
 */
final class UserEvents
{
    /**
     * The mautic.user_logout event is dispatched during the logout routine giving a chance to carry out tasks before
     * the session is lost.
     *
     * The event listener receives a Mautic\UserBundle\Event\LogoutEvent instance.
     */
    public const string USER_LOGOUT = 'mautic.user_logout';

    /**
     * The mautic.user_authentication_content event is dispatched to collect HTML from plugins to be injected into the UI to assist with
     * authentication.
     *
     * The event listener receives a Mautic\UserBundle\Event\AuthenticationContentEvent instance.
     */
    public const string USER_AUTHENTICATION_CONTENT = 'mautic.user_authentication_content';

    /**
     * The mautic.user_form_post_local_password_authentication event is dispatched after mautic checks if user's local password is correct
     * This can be used to validate passwords, usernames, etc.
     *
     * The event listener receives a Mautic\UserBundle\Event\AuthenticationContentEvent instance.
     */
    public const string USER_FORM_POST_LOCAL_PASSWORD_AUTHENTICATION ='mautic.user_form_post_local_password_authentication';

    /**
     * The mautic.user_password_strength_validation event is dispatched after mautic checks if user's password meets the strength requirements
     * This can be used to add custom password requirements.
     *
     * The event listener receives a Mautic\UserBundle\Event\PasswordStrengthValidateEvent instance.
     */
    public const string USER_PASSWORD_STRENGTH_VALIDATION = 'mautic.user_password_strength_validation';
}
