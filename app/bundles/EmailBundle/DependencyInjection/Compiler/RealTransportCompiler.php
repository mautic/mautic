<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RealTransportCompiler implements CompilerPassInterface
{
    /**
     * The swiftmailer.transport.real is only set by a Swiftmailer bundle's dependency injection compiler when email queuing (to filesystem) is enabled.
     * This makes it tricky to use the "real" transport in services which is helpful for services to have access to the real transport for processing
     * webhooks and the like. So this makes sure that swiftmailer.transport.real service is available regardless.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('swiftmailer.transport.real')) {
            return;
        }

        if ($transportParam = $container->getParameter('mautic.mailer_transport')) {
            if ('gmail' === $transportParam) {
                $transportParam = 'smtp';
            }
            if ($container->hasDefinition($transportParam)) {
                $container->setAlias('swiftmailer.transport.real', $transportParam);
            } elseif ($container->hasDefinition('swiftmailer.mailer.transport.'.$transportParam)) {
                $container->setAlias('swiftmailer.transport.real', 'swiftmailer.mailer.transport.'.$transportParam);
            } elseif ('test' === MAUTIC_ENV) {
                // Mailer is disabled in test
                $container->setAlias('swiftmailer.transport.real', 'swiftmailer.mailer.default.transport.null');
            } else {
                $container->setAlias('swiftmailer.transport.real', sprintf('swiftmailer.mailer.default.transport.%s', $transportParam));
            }
        }
    }
}
