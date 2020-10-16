<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Mautic\EmailBundle\Swiftmailer\SwiftmailerTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SwiftmailerDynamicMailerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('swiftmailer.mailer.default.transport.dynamic')) {
            return;
        }

        $definitionDecorator = $container->getDefinition('swiftmailer.mailer.default.transport.dynamic');
        $definitionDecorator->setFactory([SwiftmailerTransportFactory::class, 'createTransport']);
        $definitionDecorator->setArgument(3, new Reference('service_container'));
    }
}
