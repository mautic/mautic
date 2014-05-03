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

$collection->add('mautic_core_index', new Route('/', array(
    //@TODO Change to a different landing page
    '_controller' => 'MauticCoreBundle:Default:index'
)));

$collection->add('remove_trailing_slash', new Route( '/{url}',
    array(
        '_controller' => 'MauticCoreBundle:Common:removeTrailingSlash',
    ),
    array(
        'url' => '.*/$',
        '_method' => 'GET',
    )
));

return $collection;