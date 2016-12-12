<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;

//loads Mautic's custom routing in src/Mautic/BaseBundle/Routing/MauticLoader.php which
//loads all of the Mautic bundles' routing.php files
$collection = new RouteCollection();
$collection->addCollection($loader->import('.', 'mautic'));

return $collection;
