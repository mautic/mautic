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

//Listener to prepopulate information such as bundle name, action name, template name, etc into the request
//attributes for use in the templates
$listener = new Definition("Mautic\CoreBundle\EventListener\MauticListener");
$listener->addTag("kernel.event_listener", array(
    "event"  => "kernel.controller",
    "method" => "onKernelController"
));
$container->setDefinition("mautic.events.action_listener", $listener);

//Mautic's custom routing
$container->setDefinition ("mautic_core.routing_loader",
    new Definition(
        "Mautic\CoreBundle\Routing\RouteLoader",
        array(
            new Reference("service_container"),
            "%mautic.bundles%"
        )
    )
)->addTag("routing.loader");

//Mautic's menu renderer
$container->setDefinition("mautic_core.menu_renderer",
    new Definition(
        "Mautic\CoreBundle\Menu\MenuRenderer",
        array(
            new Reference("templating"),
            new Reference("knp_menu.matcher"),
            //"%knp_menu.renderer.list.options%",
            "%kernel.charset%"
        )
    )
)
->addTag("knp_menu.renderer", array("alias" => "mautic"));

//Mautic's MenuBuilder class
$container->setDefinition("mautic_core.menu_builder",
    new Definition(
        "Mautic\CoreBundle\Menu\MenuBuilder",
        array(
            new Reference("knp_menu.factory"),
            new Reference("knp_menu.matcher"),
            "%mautic.bundles%"
        )
    )
)
->addMethodCall("setContainer", array(
    new Reference("service_container")
));

//Mautic's MenuHelper class
$container->setDefinition("mautic_core.menuhelper",
    new Definition(
        "Mautic\CoreBundle\Menu\MenuHelper"
    )
)->addTag("templating.helper", array("alias" => "menu_helper"));

//Mautic's main menu
$container->setDefinition("mautic_core.menu.main",
    new Definition(
        "Knp\Menu\MenuItem",
        array(
            new Reference("request")
        )
    )
)
    ->setFactoryService("mautic_core.menu_builder")
    ->setFactoryMethod("mainMenu")
    ->setScope("request")
    ->addTag("knp_menu.menu", array("alias" => "main"));

//Mautic's breacrumbs menu
$container->setDefinition("mautic_core.menu.breadcrumbs",
    new Definition(
        "Knp\Menu\MenuItem",
        array(
            new Reference("request")
        )
    )
)
    ->setFactoryService("mautic_core.menu_builder")
    ->setFactoryMethod("breadcrumbsMenu")
    ->setScope("request")
    ->addTag("knp_menu.menu", array("alias" => "breadcrumbs"));

//Database table prefix
$container->setDefinition ("mautic_core.tblprefix_subscriber",
    new Definition(
        "Mautic\CoreBundle\Subscriber\TablePrefixSubscriber",
        array(
            "%db_table_prefix%",
            "%mautic.bundles%"
        )
    )
)->addTag("doctrine.event_subscriber");