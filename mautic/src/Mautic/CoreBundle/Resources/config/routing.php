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

$collection->add('mautic_core_ajax', new Route('/ajax', array(
    '_controller' => 'MauticCoreBundle:Default:executeAjax'
)));

$collection->add('mautic_remove_trailing_slash', new Route( '/{url}',
    array(
        '_controller' => 'MauticCoreBundle:Common:removeTrailingSlash',
    ),
    array(
        'url' => '.*/$',
        '_method' => 'GET',
    )
));

$collection->add('mautic_core_form_action', new Route( '/action/{objectAction}/{objectModel}/{objectId}', array(
        '_controller' => 'MauticCoreBundle:Form:execute',
        'objectId'    => 0,
        'objectModel' => ''
    )
));

return $collection;