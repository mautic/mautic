<?php

declare(strict_types=1);

namespace Mautic\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This will replace $options in the
 * \Mautic\UserBundle\DependencyInjection\Firewall\Factory\MauticSsoFactory::createAuthenticator.
 */
class SsoAuthenticatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $ssoAuthenticatorId = 'security.authenticator.mautic_sso.main';
        if (!$container->hasDefinition($ssoAuthenticatorId)) {
            throw new ServiceNotFoundException($ssoAuthenticatorId);
        }

        $formLoginAuthenticatorId = 'security.authenticator.form_login.main';
        if (!$container->hasDefinition($formLoginAuthenticatorId)) {
            throw new ServiceNotFoundException($formLoginAuthenticatorId);
        }

        $loginFormAuthenticator = $container->getDefinition($formLoginAuthenticatorId);
        $formLoginOptions       = $loginFormAuthenticator->getArgument(4);

        if (!is_array($formLoginOptions)) {
            throw new InvalidArgumentException('The $options parameter must be an array. Maybe Symfony moved the parameter for the "form_login"?');
        }

        $container->getDefinition($ssoAuthenticatorId)
            ->replaceArgument('$options', $formLoginOptions);
    }
}
