<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('email:emails:viewown', 'email:emails:viewother'), 'MATCH_ONE')) {
    return array();
}

$items = array(
    'mautic.email.menu.root' => array(
        'id' => 'mautic_email_root',
        'iconClass' => 'fa-send',
        'children' => array(
            'mautic.email.menu.index' => array(
                'route'    => 'mautic_email_index'
            )
        )
    )
);

//add category level
\Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems($items['mautic.email.menu.root']['children'], 'email', $security);

return array(
    'priority' => 6,
    'items'    => $items
);