<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TranslationsPass.
 */
class TranslationsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $translator = $container->findDefinition('translator.default');

        if ($translator !== null) {
            // Disable cache for dev environment
            if (MAUTIC_ENV === 'dev') {
                $translatorOptions              = $translator->getArgument(3);
                $translatorOptions['cache_dir'] = null;
                $translator->replaceArgument(3, $translatorOptions);
            }
        }
    }
}
