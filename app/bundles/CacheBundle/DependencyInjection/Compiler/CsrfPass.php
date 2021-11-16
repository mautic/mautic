<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\DependencyInjection\Compiler;

use Mautic\CacheBundle\Csrf\CacheTokenStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CsrfPass implements CompilerPassInterface
{
    /**
     * Defining the CacheTokenStorage here rather than in this bundle's config.php.
     * I don't want to expose this as a normal public service, and it's not possible
     * to just replace an existing service, so this seems the best way - here we
     * overwrite the class name for the security.csrf.token_storage and then set
     * the constructor arguments to what our class requires.
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition('security.csrf.token_storage');

        $definition->setClass(CacheTokenStorage::class)
            ->setArguments([
                new Reference('mautic.cache.provider'),
                new Reference('session'),
            ]);
    }
}
