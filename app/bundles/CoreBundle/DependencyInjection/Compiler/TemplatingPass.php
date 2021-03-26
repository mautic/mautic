<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.

 Modified for
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\CoreBundle\Templating\Helper\FormHelper;
use Mautic\CoreBundle\Templating\Helper\SlotsHelper;
use Mautic\CoreBundle\Templating\Helper\TranslatorHelper;
use Mautic\CoreBundle\Templating\TemplateNameParser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class TemplatingPass.
 */
class TemplatingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('templating')) {
            return;
        }

        if ($container->hasDefinition('templating.helper.assets')) {
            $container->getDefinition('templating.helper.assets')
                ->setClass(AssetsHelper::class)
                ->addMethodCall('setPathsHelper', [new Reference('mautic.helper.paths')])
                ->addMethodCall('setAssetHelper', [new Reference('mautic.helper.assetgeneration')])
                ->addMethodCall('setSiteUrl', ['%mautic.site_url%'])
                ->addMethodCall('setVersion', ['%mautic.secret_key%', MAUTIC_VERSION])
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.engine.php')) {
            $container->getDefinition('templating.engine.php')
                ->setClass(PhpEngine::class)
                ->addMethodCall(
                    'setDispatcher',
                    [new Reference('event_dispatcher')]
                )
                ->addMethodCall(
                    'setRequestStack',
                    [new Reference('request_stack')]
                )
                ->setPublic(true);
        }

        if ($container->hasDefinition('debug.templating.engine.php')) {
            $container->getDefinition('debug.templating.engine.php')
                ->setClass(PhpEngine::class)
                ->addMethodCall(
                    'setDispatcher',
                    [new Reference('event_dispatcher')]
                )
                ->addMethodCall(
                    'setRequestStack',
                    [new Reference('request_stack')]
                )
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.helper.slots')) {
            $container->getDefinition('templating.helper.slots')
                ->setClass(SlotsHelper::class)
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.name_parser')) {
            $container->getDefinition('templating.name_parser')
                ->setClass(TemplateNameParser::class)
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.helper.form')) {
            $container->getDefinition('templating.helper.form')
                ->setClass(FormHelper::class)
                ->setPublic(true);
        }

        if ($container->hasDefinition('templating.helper.translator')) {
            $container->getDefinition('templating.helper.translator')
                ->setClass(TranslatorHelper::class)
                ->setPublic(true);
        }
    }
}
