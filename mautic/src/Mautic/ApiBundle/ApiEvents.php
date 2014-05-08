<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle;

final class ApiEvents
{
    /**
     * The mautic.client_post_save event is thrown right after an API client is persisted.
     *
     * The event listener receives a
     * Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    const CLIENT_POST_SAVE   = 'mautic.client_post_save';

    /**
     * The mautic.client_post_delete event is thrown after an API client is deleted.
     *
     * The event listener receives a
     * Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    const CLIENT_POST_DELETE   = 'mautic.client_post_delete';
}