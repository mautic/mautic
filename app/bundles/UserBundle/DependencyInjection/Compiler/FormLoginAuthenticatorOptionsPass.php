<?php

declare(strict_types=1);

namespace Mautic\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormLoginAuthenticatorOptionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('mautic.security.authenticator.form_login.decorator')) {
            return;
        }

        $decoratedServiceId = 'mautic.security.authenticator.form_login.decorator.inner';
        if (!$container->has($decoratedServiceId)) {
            return;
        }

        $decoratedService = $container->getDefinition($decoratedServiceId);
        // Grab the options from the original definition
        $options          = $decoratedService->getArgument(4);

        $decorator = $container->getDefinition('mautic.security.authenticator.form_login.decorator');
        // Set the options for our decorated service
        $decorator->replaceArgument(3, $options);
    }
}
