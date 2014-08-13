<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('mautic_assetcategory_index', new Route('/assets/categories/{page}',
    array(
        '_controller' => 'MauticAssetBundle:Category:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_assetcategory_action', new Route('/assets/categories/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticAssetBundle:Category:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_asset_index', new Route('/assets/{page}',
    array(
        '_controller' => 'MauticAssetBundle:Page:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_asset_action', new Route('/assets/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticAssetBundle:Page:execute',
        "objectId"    => 0
    )
));

return $collection;
