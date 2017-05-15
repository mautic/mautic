<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class IntegrationPass.
 */
class IntegrationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('mautic.integration') as $id => $tags) {
            $definition = $container->findDefinition($id);

            /*
             * @deprecated: To be removed in 3.0. Set dependencies on Integration constructor instead,
             *              using the service container config.php to pass those dependencies in.
             */
            $definition->addMethodCall('setFactory', [new Reference('mautic.factory')]);

            $definition->addMethodCall('setDispatcher', [new Reference('event_dispatcher')]);
            $definition->addMethodCall('setCache', [new Reference('mautic.helper.cache_storage')]);
            $definition->addMethodCall('setEntityManager', [new Reference('doctrine.orm.entity_manager')]);
            $definition->addMethodCall('setSession', [new Reference('session')]);
            $definition->addMethodCall('setRequest', [new Reference('request_stack')]);
            $definition->addMethodCall('setRouter', [new Reference('router')]);
            $definition->addMethodCall('setTranslator', [new Reference('translator')]);
            $definition->addMethodCall('setLogger', [new Reference('monolog.logger.mautic')]);
            $definition->addMethodCall('setEncryptionHelper', [new Reference('mautic.helper.encryption')]);
            $definition->addMethodCall('setLeadModel', [new Reference('mautic.lead.model.lead')]);
            $definition->addMethodCall('setCompanyModel', [new Reference('mautic.lead.model.company')]);
            $definition->addMethodCall('setPathsHelper', [new Reference('mautic.helper.paths')]);
            $definition->addMethodCall('setNotificationModel', [new Reference('mautic.core.model.notification')]);
            $definition->addMethodCall('setFieldModel', [new Reference('mautic.lead.model.field')]);
        }
    }
}
