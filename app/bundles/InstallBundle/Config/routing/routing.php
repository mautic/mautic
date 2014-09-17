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

$collection->add('mautic_installer_home', new Route('/installer',
    array(
        '_controller' => 'MauticInstallBundle:Install:step'
    )
));

$collection->add('mautic_installer_step', new Route('/installer/step/{index}',
    array(
        '_controller' => 'MauticInstallBundle:Install:step'
    )
));

$collection->add('mautic_installer_final', new Route('/installer/final',
    array(
        '_controller' => 'MauticInstallBundle:Install:final'
    )
));

return $collection;
