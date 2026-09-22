<?php

use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();

// wdt
$wdt = $loader->import('@WebProfilerBundle/Resources/config/routing/wdt.php');
$wdt->addPrefix('/_wdt');
$collection->addCollection($wdt);

// profiler
$profiler = $loader->import('@WebProfilerBundle/Resources/config/routing/profiler.php');
$profiler->addPrefix('/_profiler');
$collection->addCollection($profiler);

// main
$collection->addCollection($loader->import(__DIR__.'/routing.php'));

return $collection;
