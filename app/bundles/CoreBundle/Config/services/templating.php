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

//Templating overrides
$container->setParameter('templating.helper.assets.class', 'Mautic\CoreBundle\Templating\Helper\AssetsHelper');
$container->setParameter('templating.helper.slots.class', 'Mautic\CoreBundle\Templating\Helper\SlotsHelper');
$container->setParameter('templating.name_parser.class', 'Mautic\CoreBundle\Templating\TemplateNameParser');

//Template helpers
$container->setDefinition('mautic.helper.template.date',
    new Definition(
        'Mautic\CoreBundle\Templating\Helper\DateHelper',
        array(
            new Reference('mautic.factory')
        )
    ))
    ->addTag('templating.helper', array('alias' => 'date'))
    ->setScope('request');

$container->setDefinition('mautic.helper.template.exception',
    new Definition(
        'Mautic\CoreBundle\Templating\Helper\ExceptionHelper',
        array(
            '%kernel.root_dir%'
        )
    ))
    ->addTag('templating.helper', array('alias' => 'exception'))
    ->setScope('request');

$container->setDefinition('mautic.helper.template.gravatar',
    new Definition(
        'Mautic\CoreBundle\Templating\Helper\GravatarHelper',
        array(
            new Reference('mautic.factory')
        )
    ))
    ->addTag('templating.helper', array('alias' => 'gravatar'))
    ->setScope('request');

//Custom templating parser
$container->setDefinition('mautic.templating.name_parser',
    new Definition(
        'Mautic\CoreBundle\Templating\TemplateNameParser',
        array(new Reference('kernel'))
    )
);

//Asset generation helper
$container->setDefinition('mautic.helper.assetgeneration',
    new Definition (
        'Mautic\CoreBundle\Helper\AssetGenerationHelper',
        array(
            new Reference('mautic.factory')
        )
    )
);

$container->setDefinition('mautic.helper.template.canvas',
    new Definition(
        'Mautic\CoreBundle\Templating\Helper\SidebarCanvasHelper',
        array(
            new Reference('mautic.factory')
        )
    ))
    ->addTag('templating.helper', array('alias' => 'canvas'))
    ->setScope('request');