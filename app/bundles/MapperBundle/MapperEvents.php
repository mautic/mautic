<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle;

final class MapperEvents
{
    /**
     * The mapper.on_fetch_icons event is thrown to fetch icons of menu items.
     *
     * The event listener receives a
     * Mautic\MapperBundle\Event\IconEvent instance.
     *
     * @var string
     */
    const FETCH_ICONS = 'mapper.on_fetch_icons';
    const FORM_ON_BUILD = 'mapper.on_form_build';
}
