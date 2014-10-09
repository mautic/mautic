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

//Factory class
$factory = new Definition(
    'Mautic\CoreBundle\Factory\MauticFactory',
    array(
        new Reference('service_container')
    )
);
$container->setDefinition('mautic.factory', $factory);

//Routing
$container->setDefinition ('mautic.route_loader',
    new Definition(
        'Mautic\CoreBundle\Loader\RouteLoader',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('routing.loader');

//Core permissions class
$container->setDefinition ('mautic.security',
    new Definition(
        'Mautic\CoreBundle\Security\Permissions\CorePermissions',
        array(
            new Reference('translator'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('security.context'),
            '%mautic.bundles%',
            '%mautic.parameters%'
        )
    )
);

//Translation loader
$container->setDefinition('mautic.translation.loader',
    new Definition(
        'Mautic\CoreBundle\Loader\TranslationLoader',
        array(
            new Reference('mautic.factory'))
    ))
    ->addTag('translation.loader', array('alias' => 'mautic'));
