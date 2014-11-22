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

//register social media
$collection->add('mautic_social_index', new Route('/social/config',
    array('_controller' => 'MauticSocialBundle:Social:index')
));

$collection->add('mautic_social_callback', new Route('/social/oauth2callback/{network}',
    array('_controller' => 'MauticSocialBundle:Social:oAuth2Callback')
));

$collection->add('mautic_social_postauth', new Route('/social/oauth2/status',
    array('_controller' => 'MauticSocialBundle:Social:oAuthStatus')
));

return $collection;
