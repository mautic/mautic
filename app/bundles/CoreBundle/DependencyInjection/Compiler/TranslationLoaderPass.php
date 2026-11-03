<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Translation\MauticResourceTranslator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TranslationLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('translator.default')) {
            return;
        }

        $translatorLoader  = $container->getDefinition('translator.default');
        $translatorOptions = $translatorLoader->getArgument(4);
        $warmupLocales     = $translatorLoader->getArgument(5) ?: array_keys($translatorOptions['resource_files'] ?? []);

        // Registers Mautic's translation resource lazily, in place of the loadCatalogue()
        // override that Symfony 8 closed off by making FrameworkBundle's Translator final.
        // It decorates translator.default, the concrete implementation, because that is the
        // only thing in the chain exposing addResource().
        $container->register('translator.mautic_resource', MauticResourceTranslator::class)
            ->setDecoratedService('translator.default', 'translator.mautic_resource.inner')
            ->setArgument(0, new Reference('translator.mautic_resource.inner'))
            ->setArgument(1, $warmupLocales);

        if (MAUTIC_ENV === 'prod') {
            return;
        }

        // Disable cache for dev and test environments
        $translatorOptions['cache_dir'] = null;
        $translatorLoader->replaceArgument(4, $translatorOptions);
    }
}
