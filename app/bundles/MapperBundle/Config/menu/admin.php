<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$items = array();

if ($security->isGranted('mapper:config:full')) {
    $items['mautic.mapper.menu.config'] = array(
        'route'           => 'mautic_mapper_index',
        'linkAttributes'  => array(
            'data-toggle'    => 'ajax',
            'id'             => 'mautic_mapper_index'
        ),
        'labelAttributes' => array(
            'class' => 'nav-item-name'
        ),
        'extras'          => array(
            'iconClass' => 'fa-share-alt'
        )
    );
}

return $items;
