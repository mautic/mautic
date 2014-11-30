<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
use Mautic\CategoryBundle\Helper\MenuHelper;

if (!$security->isGranted('campaign:campaigns:view')) {
    return array();
}

$items = array(
    'mautic.campaigns.menu.root' => array(
        'id'        => 'mautic_campaigns_root',
        'iconClass' => 'fa-clock-o',
        'children'  => array(
            'mautic.campaign.menu.index' => array(
                'route' => 'mautic_campaign_index'
            )
        )
    )
);

//add category level
MenuHelper::addCategoryMenuItems($items['mautic.campaigns.menu.root']['children'], 'campaign', $security);

return array(
    'priority' => 4,
    'items'    => $items
);