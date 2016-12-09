<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle;

/**
 * Class ApiEvents.
 */
final class ApiEvents
{
    /**
     * The mautic.client_pre_save event is thrown right before an API client is persisted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    const CLIENT_PRE_SAVE = 'mautic.client_pre_save';

    /**
     * The mautic.client_post_save event is thrown right after an API client is persisted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    const CLIENT_POST_SAVE = 'mautic.client_post_save';

    /**
     * The mautic.client_post_delete event is thrown after an API client is deleted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    const CLIENT_POST_DELETE = 'mautic.client_post_delete';

    /**
     * The mautic.build_api_route event is thrown to build Mautic API routes.
     *
     * The event listener receives a Mautic\CoreBundle\Event\RouteEvent instance.
     *
     * @var string
     */
    const BUILD_ROUTE = 'mautic.build_api_route';
}
