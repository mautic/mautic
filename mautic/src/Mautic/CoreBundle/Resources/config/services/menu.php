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

//Menu renderer
$container->setDefinition('mautic.menu_renderer',
    new Definition(
        'Mautic\CoreBundle\Menu\MenuRenderer',
        array(
            new Reference('templating'),
            new Reference('knp_menu.matcher'),
            //'%knp_menu.renderer.list.options%',
            '%kernel.charset%'
        )
    )
)
    ->addTag('knp_menu.renderer', array('alias' => 'mautic'));

//MenuBuilder class
$container->setDefinition('mautic.menu_builder',
    new Definition(
        'Mautic\CoreBundle\Menu\MenuBuilder',
        array(
            new Reference('knp_menu.factory'),
            new Reference('knp_menu.matcher'),
            new Reference('security.context'),
            new Reference('mautic.security')
        )
    )
)
    ->addMethodCall('setContainer', array(
        new Reference('service_container')
    ));

//MenuHelper class
$container->setDefinition('mautic.menuhelper',
    new Definition(
        'Mautic\CoreBundle\Menu\MenuHelper'
    )
)
    ->addTag('templating.helper', array('alias' => 'menu_helper'));

//Main menu
$container->setDefinition('mautic.menu_main',
    new Definition(
        'Knp\Menu\MenuItem',
        array(
            new Reference('request')
        )
    )
)
    ->setFactoryService('mautic.menu_builder')
    ->setFactoryMethod('mainMenu')
    ->setScope('request')
    ->addTag('knp_menu.menu', array('alias' => 'main'));

//Breacrumbs menu
$container->setDefinition('mautic.menu_breadcrumbs',
    new Definition(
        'Knp\Menu\MenuItem',
        array(
            new Reference('request')
        )
    )
)
    ->setFactoryService('mautic.menu_builder')
    ->setFactoryMethod('breadcrumbsMenu')
    ->setScope('request')
    ->addTag('knp_menu.menu', array('alias' => 'breadcrumbs'));

//Admin menu
$container->setDefinition('mautic.menu_admin',
    new Definition(
        'Knp\Menu\MenuItem',
        array(
            new Reference('request')
        )
    )
)
    ->setFactoryService('mautic.menu_builder')
    ->setFactoryMethod('adminMenu')
    ->setScope('request')
    ->addTag('knp_menu.menu', array('alias' => 'admin'));