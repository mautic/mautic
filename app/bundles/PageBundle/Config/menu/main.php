<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('page:pages:viewown', 'page:pages:viewother'), 'MATCH_ONE')) {
    return array();
}

$items = array(
    'mautic.page.menu.root' => array(
        'id' => 'mautic_page_root',
        'iconClass' => 'fa-file-text-o',
        'children'  => array(
            'mautic.page.menu.index' => array(
                'route' => 'mautic_page_index',
            )
        )
    )
);

//add category level
\Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems($items['mautic.page.menu.root']['children'], 'page', $security);

return array(
    'priority' => 9,
    'items'    => $items
);