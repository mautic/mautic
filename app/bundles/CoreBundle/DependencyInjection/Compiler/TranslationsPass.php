<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('translator')) {
            return;
        }

        $translator        = $container->findDefinition('translator');
        $translatorOptions = $translator->getArgument(4);

        $translatorOptions['cache_vary'] = [];

        $translator->replaceArgument(4, $translatorOptions);

        $container->register('translator.decorated', Translator::class)
            ->setDecoratedService('translator', 'translator.decorated.inner', -100)
            ->setArgument(0, new Reference('translator.decorated.inner'));
    }
}
