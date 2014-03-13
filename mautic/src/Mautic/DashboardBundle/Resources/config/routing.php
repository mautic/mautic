<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('mautic_dashboard_homepage', new Route('/', array(
    '_controller' => 'MauticDashboardBundle:Default:index',
)));

return $collection;
