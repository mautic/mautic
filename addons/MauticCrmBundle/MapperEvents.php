<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle;

final class MapperEvents
{
    /**
     * The mapper.on_fetch_icons event is thrown to fetch icons of menu items.
     *
     * The event listener receives a
     * MauticAddon\MauticCrmBundle\Event\IconEvent instance.
     *
     * @var string
     */
    const FETCH_ICONS = 'mapper.on_fetch_icons';
    const CLIENT_FORM_ON_BUILD = 'mapper.on_client_form_build';
    const OBJECT_FORM_ON_BUILD = 'mapper.on_object_form_build';
    const CALLBACK_API = 'mapper.on_callback_api';
    const SYNC_DATA = 'mapper.on_sync_data';
}
