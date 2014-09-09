<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();

//wdt
$wdt = $loader->import("@WebProfilerBundle/Resources/config/routing/wdt.xml");
$wdt->addPrefix('/_wdt');
$collection->addCollection($wdt);

//profiler
$profiler = $loader->import("@WebProfilerBundle/Resources/config/routing/profiler.xml");
$profiler->addPrefix('/_profiler');
$collection->addCollection($profiler);

//configurator
$configurator = $loader->import("@SensioDistributionBundle/Resources/config/routing/webconfigurator.xml");
$configurator->addPrefix('/_configurator');
$collection->addCollection($configurator);

//error pages
//$errors = $loader->import("@WebfactoryExceptionsBundle/Resources/config/routing.yml");
//$collection->addCollection($errors);

//main
$collection->addCollection($loader->import("routing.php"));

return $collection;
