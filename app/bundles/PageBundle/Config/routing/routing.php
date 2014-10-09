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

$collection->add('mautic_page_index', new Route('/pages/{page}',
    array(
        '_controller' => 'MauticPageBundle:Page:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_page_action', new Route('/pages/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticPageBundle:Page:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_page_tracker', new Route('/p/page/tracker.gif',
    array(
        '_controller' => 'MauticPageBundle:Public:trackingImage'
    )
));

$collection->add('mautic_page_public', new Route('/p/page/{slug1}/{slug2}/{slug3}',
    array(
        '_controller' => 'MauticPageBundle:Public:index',
        "slug2"       => '',
        "slug3"       => ''
    )
));

return $collection;
