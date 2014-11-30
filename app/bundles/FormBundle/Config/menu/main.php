<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('form:forms:viewown', 'form:forms:viewother'), 'MATCH_ONE')) {
    return array();
}

$items = array(
    'mautic.form.form.menu.root' => array(
        'id'        => 'mautic_form_root',
        'iconClass' => 'fa-pencil-square-o',
        'children'  => array(
            'mautic.form.form.menu.index' => array(
                'route' => 'mautic_form_index'
            )
        )
    )
);

//add category level
\Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems($items['mautic.form.form.menu.root']['children'], 'form', $security);

return array(
    'priority' => 7,
    'items'    => $items
);