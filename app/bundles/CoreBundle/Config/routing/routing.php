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

$collection->add('mautic_core_ajax', new Route('/ajax', array(
    '_controller' => 'MauticCoreBundle:Ajax:delegateAjax'
)));

$collection->add('mautic_remove_trailing_slash', new Route( '/{url}',
    array(
        '_controller' => 'MauticCoreBundle:Common:removeTrailingSlash',
    ),
    array(
        'url'     => '.*/$',
        '_method' => 'GET'
    )
));

$collection->add('mautic_core_update', new Route('/update', array(
    '_controller' => 'MauticCoreBundle:Update:index'
)));

$collection->add('mautic_core_form_action', new Route( '/action/{objectAction}/{objectModel}/{objectId}', array(
        '_controller' => 'MauticCoreBundle:Form:execute',
        'objectId'    => 0,
        'objectModel' => ''
    )
));

return $collection;
