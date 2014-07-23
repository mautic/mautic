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
use Symfony\Component\DependencyInjection\Parameter;

//Factory class
$factory = new Definition(
    'Mautic\CoreBundle\Factory\MauticFactory',
    array(
        new Reference('event_dispatcher'),
        new Reference('doctrine'),
        new Reference('request_stack'),
        new Reference('security.context'),
        new Reference('mautic.security'),
        new Reference('jms_serializer'),
        new Reference('session'),
        new Reference('templating'),
        new Reference('translator'),
        new Reference('validator'),
        new Reference('router'),
        '%mautic.parameters%'
    )
);
$container->setDefinition('mautic.factory', $factory);

//Routing
$container->setDefinition ('mautic.route_loader',
    new Definition(
        'Mautic\CoreBundle\Routing\RouteLoader',
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

//Custom template engine
$container->setDefinition('templating.engine.mautic',
    new Definition(
        'Mautic\CoreBundle\Templating\Engine\MauticEngine',
        array(
            new Reference('templating.name_parser'),
            new Reference('service_container'),
            new Reference('templating.loader'),
            new Reference('templating.globals'),
        )
    ))
    ->addMethodCall('setCharset', array('%kernel.charset%'))
    ->addTag('templating.engine', array('alias' => 'mautic'));

//Custom loader for assetic support
$container->setDefinition('mautic.assetic.loader',
    new Definition(
        'Mautic\CoreBundle\Templating\Loader\MauticLoader',
        array(
            new Reference('assetic.asset_factory')
        )
    )
)
    ->addTag('assetic.formula_loader', array('alias' => 'mautic'));

//Add an assetic alias for the custom engine/loader
$container->setDefinition('mautic.assetic.helper',
    new Definition(
        'Symfony\Bundle\AsseticBundle\Templating\StaticAsseticHelper',
        array(
            new Reference('templating.helper.assets'),
            new Reference('assetic.asset_factory')
        )
    )
)
    ->setScope('request')
    ->addTag('templating.helper', array('alias' => 'assetic'));

//Custom slots helper replacement
$container->setDefinition('mautic.core.helper.templateblocks',
    new Definition(
        'Mautic\CoreBundle\Templating\Helper\BlockHelper',
        array(
            new Reference('templating.helper.assets')
        )
    ))
    ->addTag('templating.helper', array('alias' => 'blocks'))
    ->setScope('request');

