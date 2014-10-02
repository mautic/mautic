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

$collection->add('mautic_api_getforms', new Route('/forms.{_format}',
    array(
        '_controller' => 'MauticFormBundle:Api\FormApi:getEntities',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml'
    )
));

$collection->add('mautic_api_getform', new Route('/forms/{id}.{_format}',
    array(
        '_controller' => 'MauticFormBundle:Api\FormApi:getEntity',
        '_format' => 'json'
    ),
    array(
        '_method' => 'GET',
        '_format' => 'json|xml',
        'id'      => '\d+'
    )
));

return $collection;