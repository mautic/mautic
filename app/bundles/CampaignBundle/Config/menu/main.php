<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.campaigns.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_campaigns_root'
        ),
        'extras'=> array(
            'iconClass' => 'fa-clock-o'
        ),
        'display' => ($security->isGranted('campaign:campaigns:view')) ? true : false,
        'children' => array(
            'mautic.campaign.menu.index' => array(
                'route'    => 'mautic_campaign_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_campaign_index'
                ),
                'children' => array(
                    'mautic.campaign.menu.new' => array(
                        'route'    => 'mautic_campaign_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_campaign_action|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.campaign.menu.edit' => array(
                        'route'           => 'mautic_campaign_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_campaign_action|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.campaign.menu.view' => array(
                        'route'           => 'mautic_campaign_action',
                        'routeParameters' => array("objectAction"  => "view"),
                        'extras'  => array(
                            'routeName' => 'mautic_campaign_action|view'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

//add category level
\Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems(
    $items['mautic.campaigns.menu.root']['children'],
    'campaign',
    $security
);

return $items;
