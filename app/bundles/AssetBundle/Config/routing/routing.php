<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('mautic_asset_buildertoken_index', new Route('/asset/buildertokens/{page}',
    array(
        '_controller' => 'MauticAssetBundle:SubscribedEvents\BuilderToken:index',
        'page'        => 1
    ),
    array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_asset_index', new Route('/assets/{page}',
    array(
        '_controller' => 'MauticAssetBundle:Asset:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_asset_action', new Route('/assets/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticAssetBundle:Asset:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_asset_download', new Route('/p/asset/{slug}',
    array(
        '_controller' => 'MauticAssetBundle:Public:download',
        "slug"    => ''
    )
));

return $collection;
