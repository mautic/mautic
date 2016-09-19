<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class EventPass
 */
class EventPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('mautic.event_subscriber') as $id => $tags) {
            $definition = $container->findDefinition($id);

            if (!in_array(CommonSubscriber::class, class_parents($definition->getClass()))) {
                continue;
            }

            $definition->addMethodCall('setTemplating', array(new Reference('mautic.helper.templating')));
            $definition->addMethodCall('setRequest', array(new Reference('request_stack')));
            $definition->addMethodCall('setSecurity', array(new Reference('mautic.security')));
            $definition->addMethodCall('setSerializer', array(new Reference('jms_serializer')));
            $definition->addMethodCall('setSystemParameters', array(new Parameter('mautic.parameters')));
            $definition->addMethodCall('setDispatcher', array(new Reference('event_dispatcher')));
            $definition->addMethodCall('setTranslator', array(new Reference('translator')));
            $definition->addMethodCall('setEntityManager', array(new Reference('doctrine.orm.entity_manager')));
            $definition->addMethodCall('setRouter', array(new Reference('router')));

            $class = $definition->getClass();
            $reflected = new \ReflectionClass($class);
            
            if ($reflected->hasProperty('logger')) {
                $definition->addMethodCall('setLogger', array(new Reference('monolog.logger.mautic')));
            }
            
            if ($reflected->hasProperty('session')) {
                $definition->addMethodCall('setSession', array(new Reference('session')));
            }
            
            // Temporary, for development purposes
            if ($reflected->hasProperty('factory')) {
                $definition->addMethodCall('setFactory', array(new Reference('mautic.factory')));
            }

            $definition->addMethodCall('init');
        }
    }
}
