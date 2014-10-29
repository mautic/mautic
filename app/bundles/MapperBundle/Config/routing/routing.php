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

//register social media
$collection->add('mautic_mapper_index', new Route('/mapper/config',
    array('_controller' => 'MauticMapperBundle:Mapper:index')
));

$collection->add('mautic_mapper_callback', new Route('/mapper/oauth2callback/{network}',
    array('_controller' => 'MauticMapperBundle:Mapper:oAuth2Callback')
));

$collection->add('mautic_mapper_postauth', new Route('/mapper/oauth2/status',
    array('_controller' => 'MauticMapperBundle:Mapper:oAuthStatus')
));

return $collection;
