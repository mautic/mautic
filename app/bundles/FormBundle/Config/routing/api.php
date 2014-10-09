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

$collection->add('mautic_api_getforms', new Route('/forms',
    array(
        '_controller' => 'MauticFormBundle:Api\FormApi:getEntities',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET'
    )
));

$collection->add('mautic_api_getform', new Route('/forms/{id}',
    array(
        '_controller' => 'MauticFormBundle:Api\FormApi:getEntity',
        '_format'     => 'json'
    ),
    array(
        '_method' => 'GET',
        'id'      => '\d+'
    )
));

return $collection;