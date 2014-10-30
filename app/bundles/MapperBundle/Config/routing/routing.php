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

//dashboard integrations
$collection->add('mautic_mapper_index', new Route('/mapper/dashboard',
    array('_controller' => 'MauticMapperBundle:Mapper:index')
));

//custom integration
$collection->add('mautic_mapper_integration', new Route('/mapper/integration/{network}',
    array('_controller' => 'MauticMapperBundle:Mapper:integration')
));

//custom integration object mapper
$collection->add('mautic_mapper_integration_object', new Route('/mapper/integration/{network}/{object}',
    array('_controller' => 'MauticMapperBundle:Mapper:integrationObject')
));

$collection->add('mautic_mapper_callback', new Route('/mapper/oauth2callback/{network}',
    array('_controller' => 'MauticMapperBundle:Mapper:oAuth2Callback')
));

return $collection;
