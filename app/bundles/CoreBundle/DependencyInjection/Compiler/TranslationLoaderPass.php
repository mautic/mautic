<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Translation\TranslatorLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('translator.default')) {
            return;
        }

        $translatorLoader = $container->getDefinition('translator.default');
        $translatorLoader->setClass(TranslatorLoader::class)
            ->setPublic(true);

        if (MAUTIC_ENV === 'prod') {
            return;
        }

        // Disable cache for dev and test environments
        $translatorOptions              = $translatorLoader->getArgument(4);
        $translatorOptions['cache_dir'] = null;
        $translatorLoader->replaceArgument(4, $translatorOptions);
    }
}
