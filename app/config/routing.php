<?php

use Symfony\Component\Routing\RouteCollection;

// loads Mautic's custom routing in src/Mautic/BaseBundle/Routing/MauticLoader.php which
// loads all of the Mautic bundles' routing.php files
$collection = new RouteCollection();

// loads api_platform
$apiCollection = $loader->import('.', 'api_platform');
$apiCollection->addPrefix('/api/v2/');
$collection->addCollection($apiCollection);

// loads Mautic's custom routing in src/Mautic/BaseBundle/Routing/MauticLoader.php which
// loads all of the Mautic bundles' routing.php files. It must be the LAST one in the
// collection
$collection->addCollection($loader->import('.', 'mautic'));

return $collection;
