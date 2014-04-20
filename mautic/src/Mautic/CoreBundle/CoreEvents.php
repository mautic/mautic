<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle;

final class CoreEvents
{
    /**
     * The menu.build event is thrown to render menu items.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\MenuEvent instance.
     *
     * @var string
     */
    const MENU_BUILD = 'menu.build';
}