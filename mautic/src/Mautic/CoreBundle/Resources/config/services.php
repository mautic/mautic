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

//Setup a listener to prepopulate information such as bundle name, action name, template name, etc into the request
//attributes for use in the templates
$listener = new Definition("Mautic\CoreBundle\EventListener\MauticListener");
$listener->addTag("kernel.event_listener", array(
    "event"  => "kernel.controller",
    "method" => "onKernelController"
));
$container->setDefinition("mautic.events.action_listener", $listener);

//Register Mautic's custom routing
$container->setDefinition ("mautic_base.routing_loader",
    new Definition(
        "Mautic\CoreBundle\Routing\RouteLoader",
        array(
            new Reference("service_container"),
            "%mautic.bundles%"
        )
    )
)->addTag("routing.loader");

//Register Mautic's menu renderer
$container->setDefinition("mautic_base.menu_renderer",
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

//Register Mautic's MenuBuilder class
$container->setDefinition("mautic_base.menu_builder",
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

//Register Mautic's MenuHelper class
$container->setDefinition("mautic_base.menuhelper",
    new Definition(
        "Mautic\CoreBundle\Menu\MenuHelper"
    )
)->addTag("templating.helper", array("alias" => "menu_helper"));

//Register Mautic's main menu
$container->setDefinition("mautic_base.menu.main",
    new Definition(
        "Knp\Menu\MenuItem",
        array(
            new Reference("request")
        )
    )
)
    ->setFactoryService("mautic_base.menu_builder")
    ->setFactoryMethod("mainMenu")
    ->setScope("request")
    ->addTag("knp_menu.menu", array("alias" => "main"));

//Register Mautic's breacrumbs menu
$container->setDefinition("mautic_base.menu.breadcrumbs",
    new Definition(
        "Knp\Menu\MenuItem",
        array(
            new Reference("request")
        )
    )
)
    ->setFactoryService("mautic_base.menu_builder")
    ->setFactoryMethod("breadcrumbsMenu")
    ->setScope("request")
    ->addTag("knp_menu.menu", array("alias" => "breadcrumbs"));