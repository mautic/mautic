<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

//API Route Loader
$container->setDefinition ('mautic.api_route_loader',
    new Definition(
        'Mautic\ApiBundle\Routing\RouteLoader',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('routing.loader');

//API docs loader
$container->setDefinition ('mautic.api_docs_route_loader',
    new Definition(
        'Mautic\ApiBundle\Routing\ApiDocsLoader',
        array('%kernel.environment%')
    )
)
    ->addTag('routing.loader');