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

//Custom templating parser
$container->setDefinition('mautic.templating.name_parser',
    new Definition(
        'Mautic\CoreBundle\Templating\TemplateNameParser',
        array(new Reference('kernel'))
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

//Templating overrides
$container->setParameter('templating.helper.assets.class', 'Mautic\CoreBundle\Templating\Helper\AssetsHelper');
$container->setParameter('templating.helper.slots.class', 'Mautic\CoreBundle\Templating\Helper\SlotsHelper');
$container->setParameter('templating.name_parser.class', 'Mautic\CoreBundle\Templating\TemplateNameParser');
