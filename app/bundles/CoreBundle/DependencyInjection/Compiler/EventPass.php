<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\WebhookBundle\EventListener\WebhookSubscriberBase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Class EventPass.
 */
class EventPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('mautic.event_subscriber') as $id => $tags) {
            $definition   = $container->findDefinition($id);
            $classParents = class_parents($definition->getClass());

            if (!in_array(CommonSubscriber::class, $classParents)) {
                continue;
            }

            $definition->addMethodCall('setTemplating', [new Reference('mautic.helper.templating')]);
            $definition->addMethodCall('setRequest', [new Reference('request_stack')]);
            $definition->addMethodCall('setSecurity', [new Reference('mautic.security')]);
            $definition->addMethodCall('setSerializer', [new Reference('jms_serializer')]);
            $definition->addMethodCall('setSystemParameters', [new Expression("parameter('mautic.parameters')")]);
            $definition->addMethodCall('setDispatcher', [new Reference('event_dispatcher')]);
            $definition->addMethodCall('setTranslator', [new Reference('translator')]);
            $definition->addMethodCall('setEntityManager', [new Reference('doctrine.orm.entity_manager')]);
            $definition->addMethodCall('setRouter', [new Reference('router')]);

            $class     = $definition->getClass();
            $reflected = new \ReflectionClass($class);

            if ($reflected->hasProperty('logger')) {
                $definition->addMethodCall('setLogger', [new Reference('monolog.logger.mautic')]);
            }

            // Temporary, for development purposes
            if ($reflected->hasProperty('factory')) {
                $definition->addMethodCall('setFactory', [new Reference('mautic.factory')]);
            }

            if (in_array(WebhookSubscriberBase::class, $classParents)) {
                $definition->addMethodCall('setWebhookModel', [new Reference('mautic.webhook.model.webhook')]);
            }

            $definition->addMethodCall('init');
        }
    }
}
