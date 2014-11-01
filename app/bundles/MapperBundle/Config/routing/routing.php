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

$collection->add('mautic_mapper_index', new Route('/mapper/dashboard',
    array('_controller' => 'MauticMapperBundle:Mapper:index')
));

$collection->add('mautic_mapper_integration', new Route('/mapper/integration/{application}',
    array('_controller' => 'MauticMapperBundle:Mapper:integration')
));

$collection->add('mautic_mapper_save', new Route('/mapper/integration/save/{application}',
    array('_controller' => 'MauticMapperBundle:Mapper:save')
));

$collection->add('mautic_mapper_integration_object', new Route('/mapper/integration/{application}/{object}',
    array('_controller' => 'MauticMapperBundle:Mapper:integrationObject')
));

$collection->add('mautic_mapper_callback', new Route('/mapper/oauth2callback/{application}',
    array('_controller' => 'MauticMapperBundle:Mapper:oAuth2Callback')
));

return $collection;
