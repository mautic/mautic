<?php

use Symfony\Component\Routing\RouteCollection;

// loads Mautic's custom routing in src/Mautic/BaseBundle/Routing/MauticLoader.php which
// loads all of the Mautic bundles' routing.php files
$collection = new RouteCollection();
$collection->addCollection($loader->import('.', 'mautic'));

return $collection;
