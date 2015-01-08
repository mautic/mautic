<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        array(new Reference('mautic.factory'))
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

//Override exception class for AJAX
$container->setParameter('twig.controller.exception.class', 'Mautic\CoreBundle\Controller\ExceptionController');

//Transifex class
$transifex = new Definition(
    'BabDev\Transifex\Transifex',
    array(
        array('api.username' => $container->getParameter('mautic.transifex_username'), 'api.password' => $container->getParameter('mautic.transifex_password'))
    )
);
$container->setDefinition('transifex', $transifex);

//Custom PHP log handler
$container->setParameter('monolog.handler.stream.class', 'Mautic\CoreBundle\Monolog\Handler\PhpHandler');

//Cookie helper
$container->setDefinition('mautic.helper.cookie',
    new Definition(
        'Mautic\CoreBundle\Helper\CookieHelper',
        array(
            new Reference('mautic.factory'))
    ));

//Update helper
$container->setDefinition('mautic.helper.update',
    new Definition(
        'Mautic\CoreBundle\Helper\UpdateHelper',
        array(
            new Reference('mautic.factory'))
    ));

//Cache helper
$container->setDefinition('mautic.helper.cache',
    new Definition(
        'Mautic\CoreBundle\Helper\CacheHelper',
        array(
            new Reference('mautic.factory'))
    ));


//Theme helper
$container->setDefinition('mautic.helper.theme',
    new Definition(
        'Mautic\CoreBundle\Helper\ThemeHelper',
        array(
            new Reference('mautic.factory'))
    ));

//Encryption helper
$container->setDefinition('mautic.helper.encryption',
    new Definition(
        'Mautic\CoreBundle\Helper\EncryptionHelper',
        array(
            new Reference('mautic.factory'))
    ));