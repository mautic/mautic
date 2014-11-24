<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle;

/**
 * Class CoreEvents
 */
final class CoreEvents
{
    /**
     * The mautic.build_menu event is thrown to render menu items.
     *
     * The event listener receives a Mautic\CoreBundle\Event\MenuEvent instance.
     *
     * @var string
     */
    const BUILD_MENU = 'mautic.build_menu';

    /**
     * The mautic.build_admin_menu event is thrown to render admin menu items.
     *
     * The event listener receives a Mautic\CoreBundle\Event\MenuEvent instance.
     *
     * @var string
     */
    const BUILD_ADMIN_MENU = 'mautic.build_admin_menu';

    /**
     * The mautic.build_route event is thrown to build Mautic bundle routes
     *
     * The event listener receives a Mautic\CoreBundle\Event\RouteEvent instance.
     *
     * @var string
     */
    const BUILD_ROUTE = 'mautic.build_route';

    /**
     * The mautic.global_search event is thrown to build global search results from applicable bundles
     *
     * The event listener receives a Mautic\CoreBundle\Event\GlobalSearchEvent instance.
     *
     * @var string
     */
    const GLOBAL_SEARCH = 'mautic.global_search';

    /**
     * The mautic.build_command_list event is thrown to build global search's autocomplete list
     *
     * The event listener receives a Mautic\CoreBundle\Event\CommandListEvent instance.
     *
     * @var string
     */
    const BUILD_COMMAND_LIST = 'mautic.build_command_list';

    /**
     * The mautic.on_email_failed event is thrown when an email has failed to clear the queue and is about to be deleted
     * in order to give a bundle a chance to do an action based on failed email if required
     *
     * The event listener receives a Mautic\CoreBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_FAILED = 'mautic.on_email_failed';

    /**
     * The mautic.on_email_resend event is thrown when an attempt to resend an email occurs
     * in order to give a bundle a chance to do an action based on failed email if required
     *
     * The event listener receives a Mautic\CoreBundle\Event\EmailEvent instance.
     *
     * @var string
     */
    const EMAIL_RESEND = 'mautic.on_email_resend';

    /**
     * The mautic.on_fetch_icons event is thrown to fetch icons of menu items.
     *
     * The event listener receives a Mautic\CoreBundle\Event\IconEvent instance.
     *
     * @var string
     */
    const FETCH_ICONS = 'mautic.on_fetch_icons';
}
